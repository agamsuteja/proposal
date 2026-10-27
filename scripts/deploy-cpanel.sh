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
if ! command -v npm >/dev/null 2>&1; then
  echo 'npm belum tersedia pada PATH cPanel. Aktifkan Node.js untuk build Vite terlebih dahulu.' >&2
  exit 1
fi
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
npm install --no-package-lock --no-audit --no-fund
npm run build
"$DEPLOY_PHP" artisan config:clear
# Only the additive budget migration: legacy Laravel migrations do not match this database.
"$DEPLOY_PHP" artisan migrate --path=database/migrations/2026_10_27_000001_create_department_budgets.php --force
"$DEPLOY_PHP" artisan view:clear
"$DEPLOY_PHP" artisan view:cache
printf '%s\n' 'Deployment selesai. Periksa /admin/login dan menu budget; keuangan perlu mengisi alokasi awal.'
