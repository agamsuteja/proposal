#!/bin/bash
set -euo pipefail
PROJECT_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"
DEPLOY_PHP="${PROPOSAL_DEPLOY_PHP:-/opt/cpanel/ea-php82/root/usr/bin/php}"
if [[ ! -x "$DEPLOY_PHP" ]]; then echo "PHP 8.2 tidak ditemukan: $DEPLOY_PHP" >&2; exit 1; fi
"$DEPLOY_PHP" -r 'if (PHP_VERSION_ID < 80200) { fwrite(STDERR,"Perlu PHP 8.2 atau lebih baru.\n"); exit(1); }'
[[ -f .env ]] || { echo '.env hosting belum tersedia.' >&2; exit 1; }
[[ -f composer.phar ]] || { echo 'composer.phar tidak ditemukan.' >&2; exit 1; }
[[ -f deployment/frontend/SHA256SUMS ]] || { echo 'Hasil build belum tersedia.' >&2; exit 1; }
(cd deployment/frontend && sha256sum --check SHA256SUMS)
[[ -z "$(git status --porcelain --untracked-files=no)" ]] || { echo 'Simpan perubahan lokal sebelum deployment.' >&2; exit 1; }
# Check-only also validates the imported SQL; it never runs migrations or writes data.
if [[ "${1:-}" == '--check' ]]; then
    [[ -f vendor/autoload.php ]] || { echo 'Pasang dependensi Composer terlebih dahulu.' >&2; exit 1; }
    "$DEPLOY_PHP" scripts/check-schema.php --production
    echo 'Pemeriksaan prasyarat berhasil; belum ada perubahan aplikasi/database.'
    exit 0
fi
export PATH="$(dirname "$DEPLOY_PHP"):$PATH"
"$DEPLOY_PHP" composer.phar install --no-dev --prefer-dist --no-interaction --optimize-autoloader
"$DEPLOY_PHP" artisan config:clear
"$DEPLOY_PHP" scripts/check-schema.php --production
# Block direct access to legacy uploaded documents; PHP serves them via scoped authenticated routes.
# Preserve any existing .htaccess content and append an explicit deny rule only once.
for UPLOAD_FOLDER in proposal_lampiran nota_penggunaan bukti_transfer bukti_pengembalian; do
    mkdir -p "storage/app/public/$UPLOAD_FOLDER"
    UPLOAD_RULES="storage/app/public/$UPLOAD_FOLDER/.htaccess"
    if ! test -f "$UPLOAD_RULES" || ! grep -q 'proposal-private-documents' "$UPLOAD_RULES"; then
        cat >> "$UPLOAD_RULES" <<'EOF'

# proposal-private-documents
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order allow,deny
Deny from all
</IfModule>
EOF
    fi
done
mkdir -p public/build
cp -R deployment/frontend/assets public/build/
cp deployment/frontend/manifest.json public/build/manifest.json
"$DEPLOY_PHP" artisan view:clear
"$DEPLOY_PHP" artisan view:cache
"$DEPLOY_PHP" artisan security:check
printf '%s\n' 'Deployment selesai. Login ulang dan periksa laporan rekonsiliasi. Tidak ada migrasi otomatis.'
