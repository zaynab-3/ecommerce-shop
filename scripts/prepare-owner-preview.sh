#!/usr/bin/env bash
# Runs only on an ephemeral GitHub-hosted runner, never against an existing shop.
set -euo pipefail
cd "$(dirname "$0")/.."
if [[ "${GITHUB_ACTIONS:-}" != true || -f .env ]]; then
    echo 'Owner preview requires a fresh GitHub Actions checkout without .env.' >&2
    exit 1
fi
umask 077
preview_db_password=$(openssl rand -hex 24)
preview_root_password=$(openssl rand -hex 24)
printf '::add-mask::%s\n' "$preview_db_password" "$preview_root_password"
export MYSQL_ROOT_PASSWORD="$preview_root_password" MYSQL_PASSWORD="$preview_db_password"
docker run --detach --name mesh-owner-preview-db \
    --publish 127.0.0.1:3306:3306 \
    --env MYSQL_ROOT_PASSWORD --env MYSQL_PASSWORD \
    --env MYSQL_DATABASE=mesh_owner_preview --env MYSQL_USER=mesh_preview mysql:8.4
preview_ready=false
for attempt in {1..60}; do
    if docker exec -e MYSQL_PWD="$preview_db_password" mesh-owner-preview-db mysql --host=127.0.0.1 --user=mesh_preview --database=mesh_owner_preview --execute='SELECT 1' >/dev/null 2>&1; then
        preview_ready=true
        break
    fi
    sleep 2
done
if [[ "$preview_ready" != true ]]; then echo 'Preview database failed to start.' >&2; exit 1; fi
cat > .env <<EOF
APP_NAME=MESH
APP_ENV=local
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Beirut
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mesh_owner_preview
DB_USERNAME=mesh_preview
DB_PASSWORD=$preview_db_password
SESSION_DRIVER=file
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=mesh_owner_preview
CACHE_STORE=file
LOG_CHANNEL=single
LOG_LEVEL=warning
ADMIN_PATH=manage-store
EOF
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan migrate --force
# Demo seeding is deliberately allowed only before switching this fresh app to production.
php artisan db:seed --class=DemoCatalogSeeder --force
if [[ -n "${PREVIEW_ADMIN_PUBLIC_KEY:-}" ]]; then
    php scripts/create-preview-owner.php
    openssl pkeyutl -encrypt -pubin -inkey "$RUNNER_TEMP/mesh-preview-admin-public.pem" \
        -in "$RUNNER_TEMP/mesh-preview-admin.json" -out "$RUNNER_TEMP/mesh-preview-admin.enc" \
        -pkeyopt rsa_padding_mode:oaep -pkeyopt rsa_oaep_md:sha256 -pkeyopt rsa_mgf1_md:sha256
fi
sed -i 's/^APP_ENV=local$/APP_ENV=production/' .env
php artisan view:cache
