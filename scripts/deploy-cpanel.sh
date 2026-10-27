#!/bin/bash
set -euo pipefail

# Deploy in place: preserve .env, uploaded files and the existing database.
PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"
DEPLOY_PHP="${PROPOSAL_DEPLOY_PHP:-/opt/cpanel/ea-php82/root/usr/bin/php}"
if [[ ! -x "$DEPLOY_PHP" ]]; then
  echo "PHP 8.2 tidak ditemukan: $DEPLOY_PHP" >&2
  exit 1
fi
"$DEPLOY_PHP" -r 'if (PHP_VERSION_ID < 80200) { fwrite(STDERR, "Deployment memerlukan PHP 8.2 atau lebih baru.\n"); exit(1); }'
if [[ ! -f .env ]]; then
  echo 'File .env hosting belum tersedia. Deployment dihentikan.' >&2
  exit 1
fi
if [[ ! -f composer.phar ]]; then
  echo 'composer.phar repositori tidak ditemukan.' >&2
  exit 1
fi
if [[ ! -f deployment/frontend/manifest.json || ! -f deployment/frontend/SHA256SUMS ]]; then
  echo 'Belum ada hasil build frontend di Git: deployment/frontend.' >&2
  exit 1
fi
(cd deployment/frontend && sha256sum --check SHA256SUMS)
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  echo 'Ada perubahan file terlacak di hosting. Simpan perubahan sebelum deployment.' >&2
  exit 1
fi
if [[ "${1:-}" == '--check' ]]; then
  echo 'Pemeriksaan prasyarat deployment berhasil; belum ada perubahan aplikasi/database.'
  exit 0
fi
# Composer scripts invoke PHP through PATH; keep them on the selected runtime.
export PATH="$(dirname "$DEPLOY_PHP"):$PATH"
"$DEPLOY_PHP" composer.phar install --no-dev --prefer-dist --no-interaction --optimize-autoloader
mkdir -p public/build
cp -R deployment/frontend/assets public/build/
cp deployment/frontend/manifest.json public/build/manifest.json
"$DEPLOY_PHP" artisan config:clear
# Database is managed manually through phpMyAdmin; never run migrations here.
"$DEPLOY_PHP" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (["budget_departemen", "budget_requests", "budget_approvals", "budget_transfers", "activity_logs"] as $table) {
    if (!Illuminate\Support\Facades\Schema::hasTable($table)) {
        fwrite(STDERR, "Tabel $table belum ada. Import database/sql/2026_10_27_department_budgets.sql melalui phpMyAdmin terlebih dahulu.\n");
        exit(1);
    }
}
'
"$DEPLOY_PHP" artisan view:clear
"$DEPLOY_PHP" artisan view:cache
printf '%s\n' 'Deployment selesai. Periksa /admin/login dan menu budget; keuangan perlu mengisi alokasi awal.'
