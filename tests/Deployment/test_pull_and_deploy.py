"""Isolated Git integration tests; no production deploy, network or application database."""
import fcntl
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

WRAPPER = Path(__file__).resolve().parents[2] / 'scripts/pull-and-deploy-cpanel.sh'


class PullAndDeployTest(unittest.TestCase):
    def command(self, *args, cwd=None, check=True):
        return subprocess.run(args, cwd=cwd or self.host, env=self.env, text=True,
                              capture_output=True, check=check)

    def git(self, repo, *args):
        return self.command('git', '-C', str(repo), *args, cwd=repo).stdout.strip()

    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='proposal-deploy-test-')
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        self.seed, self.remote, self.host = [self.root / x for x in ['seed', 'remote.git', 'host']]
        self.env = dict(os.environ, GIT_AUTHOR_NAME='Deploy Test', GIT_AUTHOR_EMAIL='deploy@example.test',
                        GIT_COMMITTER_NAME='Deploy Test', GIT_COMMITTER_EMAIL='deploy@example.test')
        self.command('git', 'init', '--bare', '--initial-branch=master', str(self.remote), cwd=self.root)
        self.command('git', 'init', '--initial-branch=master', str(self.seed), cwd=self.root)
        (self.seed / 'scripts').mkdir()
        shutil.copyfile(WRAPPER, self.seed / 'scripts/pull-and-deploy-cpanel.sh')
        (self.seed / '.gitignore').write_text('.env\n')
        (self.seed / 'version.txt').write_text('old')
        self.stub = '#!/bin/bash\nset -eu\ngit rev-parse HEAD > .git/test-deployed-sha\nprintf "%s\\n" "${1:-deploy}" > .git/test-deploy-mode\n'
        (self.seed / 'scripts/deploy-cpanel.sh').write_text(self.stub)
        self.git(self.seed, 'add', '.')
        self.git(self.seed, 'commit', '-m', 'Initial deployment fixture')
        self.git(self.seed, 'remote', 'add', 'origin', str(self.remote))
        self.git(self.seed, 'push', 'origin', 'master')
        self.command('git', 'clone', str(self.remote), str(self.host), cwd=self.root)
        (self.host / '.env').write_text('LOCAL_SYNTHETIC_VALUE=keep-me\n')

    def publish(self, version='new'):
        (self.seed / 'version.txt').write_text(version)
        self.git(self.seed, 'add', '.')
        self.git(self.seed, 'commit', '-m', 'Publish '+version)
        self.git(self.seed, 'push', 'origin', 'master')
        return self.git(self.seed, 'rev-parse', 'HEAD')

    def run_deploy(self, *args):
        return self.command('bash', str(self.host / 'scripts/pull-and-deploy-cpanel.sh'), *args,
                            cwd=self.root, check=False)

    def test_click_updates_master_runs_latest_deployer_and_preserves_env(self):
        (self.seed / 'scripts/deploy-cpanel.sh').write_text(self.stub + 'echo latest > .git/test-deployer-version\n')
        # Updating the running wrapper must not affect its already parsed instructions.
        (self.seed / 'scripts/pull-and-deploy-cpanel.sh').write_text(WRAPPER.read_text().replace('main() {', 'main() {\n    # newly published wrapper\n'))
        expected = self.publish()
        result = self.run_deploy()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(expected, self.git(self.host, 'rev-parse', 'HEAD'))
        self.assertEqual(expected, (self.host / '.git/test-deployed-sha').read_text().strip())
        self.assertEqual(expected, (self.host / '.git/proposal-last-successful-deploy').read_text().strip())
        self.assertEqual('latest', (self.host / '.git/test-deployer-version').read_text().strip())
        self.assertEqual('LOCAL_SYNTHETIC_VALUE=keep-me\n', (self.host / '.env').read_text())
        self.assertEqual(0, self.run_deploy().returncode)

    def test_dirty_server_is_preserved_and_not_deployed(self):
        before = self.git(self.host, 'rev-parse', 'HEAD')
        self.publish()
        (self.host / 'version.txt').write_text('operator changes')
        result = self.run_deploy()
        self.assertNotEqual(0, result.returncode)
        self.assertIn('perubahan file terlacak', result.stderr)
        self.assertEqual(before, self.git(self.host, 'rev-parse', 'HEAD'))
        self.assertEqual('operator changes', (self.host / 'version.txt').read_text())
        self.assertFalse((self.host / '.git/test-deployed-sha').exists())

    def test_unpublished_server_commit_is_not_reset_or_deployed(self):
        (self.host / 'version.txt').write_text('server commit')
        self.git(self.host, 'add', 'version.txt')
        self.git(self.host, 'commit', '-m', 'Preserve unpublished server commit')
        before = self.git(self.host, 'rev-parse', 'HEAD')
        self.publish()
        result = self.run_deploy()
        self.assertNotEqual(0, result.returncode)
        self.assertEqual(before, self.git(self.host, 'rev-parse', 'HEAD'))
        self.assertFalse((self.host / '.git/test-deployed-sha').exists())

    def test_other_branch_is_not_switched(self):
        self.git(self.host, 'checkout', '-b', 'work-in-progress')
        result = self.run_deploy()
        self.assertNotEqual(0, result.returncode)
        self.assertEqual('work-in-progress', self.git(self.host, 'branch', '--show-current'))
        self.assertFalse((self.host / '.git/test-deployed-sha').exists())

    def test_concurrent_click_does_not_run_another_deploy(self):
        with (self.host / '.git/proposal-deploy.lock').open('w') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            result = self.run_deploy()
            self.assertNotEqual(0, result.returncode)
            self.assertIn('Deployment lain', result.stderr)
            self.assertFalse((self.host / '.git/test-deployed-sha').exists())

    def test_unavailable_remote_stops_before_deploy(self):
        self.git(self.host, 'remote', 'set-url', 'origin', str(self.root / 'missing.git'))
        result = self.run_deploy()
        self.assertNotEqual(0, result.returncode)
        self.assertIn('Tidak dapat mengambil', result.stderr)
        self.assertFalse((self.host / '.git/test-deployed-sha').exists())

    def test_failed_deployment_does_not_write_success_record(self):
        (self.seed / 'scripts/deploy-cpanel.sh').write_text('#!/bin/bash\nexit 17\n')
        expected = self.publish()
        result = self.run_deploy()
        self.assertEqual(17, result.returncode)
        self.assertEqual(expected, self.git(self.host, 'rev-parse', 'HEAD'))
        self.assertFalse((self.host / '.git/proposal-last-successful-deploy').exists())

    def test_check_only_does_not_pull(self):
        before = self.git(self.host, 'rev-parse', 'HEAD')
        self.publish()
        result = self.run_deploy('--check')
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(before, self.git(self.host, 'rev-parse', 'HEAD'))
        self.assertEqual('--check', (self.host / '.git/test-deploy-mode').read_text().strip())
        self.assertFalse((self.host / '.git/proposal-last-successful-deploy').exists())


if __name__ == '__main__':
    unittest.main(verbosity=2)
