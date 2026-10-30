#!/bin/bash
set -euo pipefail

# Parse the complete function before updating its own on-disk file.
# The deployment child is loaded from the newly fetched commit.
main() {
    local project_root git_bin git_dir current target
    project_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
    cd "$project_root"
    if [[ "${1:-}" == '--check' && "$#" -eq 1 ]]; then
        exec /bin/bash "$project_root/scripts/deploy-cpanel.sh" --check
    fi
    if [[ "$#" -ne 0 ]]; then
        echo 'Gunakan tanpa argumen untuk sinkronisasi + deploy, atau --check untuk pemeriksaan tanpa pull.' >&2
        return 1
    fi
    git_bin="$(command -v git || true)"
    if [[ -z "$git_bin" ]]; then
        for git_bin in /usr/local/cpanel/3rdparty/bin/git /usr/bin/git /usr/local/bin/git; do
            [[ -x "$git_bin" ]] && break
        done
    fi
    [[ -x "$git_bin" ]] || { echo 'Git tidak tersedia pada lingkungan deployment cPanel.' >&2; return 1; }
    command -v flock >/dev/null || { echo 'flock diperlukan untuk mencegah dua deployment berjalan bersamaan.' >&2; return 1; }
    git_dir="$("$git_bin" rev-parse --absolute-git-dir)"
    exec 9>"$git_dir/proposal-deploy.lock"
    flock -n 9 || { echo 'Deployment lain sedang berjalan. Tunggu sampai selesai sebelum mencoba lagi.' >&2; return 1; }
    [[ "$("$git_bin" symbolic-ref --quiet --short HEAD)" == 'master' ]] || {
        echo 'Repository server harus berada pada branch master sebelum deployment.' >&2; return 1;
    }
    [[ -z "$("$git_bin" status --porcelain --untracked-files=no)" ]] || {
        echo 'Ada perubahan file terlacak di server. Simpan dan selesaikan perubahan tersebut sebelum deploy; tidak ada file yang ditimpa.' >&2; return 1;
    }
    [[ -f "$project_root/.env" ]] || { echo '.env hosting belum tersedia.' >&2; return 1; }
    echo 'Mengambil perubahan terbaru GitHub master...'
    # Do not print remote URLs or raw transport errors: credentials may be embedded in them.
    if ! GIT_TERMINAL_PROMPT=0 "$git_bin" fetch --quiet origin refs/heads/master:refs/remotes/origin/master >/dev/null 2>&1; then
        echo 'Tidak dapat mengambil GitHub master. Periksa koneksi dan akses remote origin untuk akun cPanel tanpa prompt login.' >&2
        return 1
    fi
    current="$("$git_bin" rev-parse HEAD)"
    target="$("$git_bin" rev-parse --verify 'refs/remotes/origin/master^{commit}')"
    if ! "$git_bin" merge-base --is-ancestor "$current" "$target"; then
        echo 'Commit server tidak dapat diperbarui dengan fast-forward. Sinkronkan commit lokal/server dengan GitHub; tidak ada reset atau force pull.' >&2
        return 1
    fi
    if [[ "$current" != "$target" ]]; then
        if ! "$git_bin" -c merge.autoStash=false merge --ff-only "$target" >/dev/null 2>&1; then
            echo 'Pembaruan berhenti karena Git tidak dapat menerapkan fast-forward. Periksa file lokal yang bertabrakan.' >&2
            return 1
        fi
    fi
    [[ "$("$git_bin" rev-parse HEAD)" == "$target" ]] || { echo 'Commit server berubah selama sinkronisasi. Deployment dihentikan.' >&2; return 1; }
    echo "Commit aktual yang akan di-deploy: $target"
    /bin/bash "$project_root/scripts/deploy-cpanel.sh"
    [[ "$("$git_bin" rev-parse HEAD)" == "$target" ]] || { echo 'Commit berubah selama deploy; periksa hasil sebelum melanjutkan.' >&2; return 1; }
    printf '%s\n' "$target" > "$git_dir/proposal-last-successful-deploy"
    echo "Sinkronisasi dan deployment selesai: $target"
}

{ main "$@"; exit; }
