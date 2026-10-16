#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
source scripts/cloud-php.sh
composer install --prefer-dist --no-interaction
if [ ! -f .env ]; then cp .env.example .env; chmod 600 .env; fi
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!config("app.key")) { Illuminate\Support\Facades\Artisan::call("key:generate", ["--force" => true]); }
if (config("database.default") === "sqlite") {
    $path = config("database.connections.sqlite.database");
    if ($path !== ":memory:" && !file_exists($path)) { touch($path); }
}
'
php artisan migrate --force
php artisan db:seed --force
if [ ! -e public/storage ] && [ ! -L public/storage ]; then php artisan storage:link; fi
php artisan view:cache
printf '%s\n' 'Setup complete. Demo catalog: php artisan db:seed --class=DemoCatalogSeeder' 'Create an owner: php artisan shop:admin your-email@example.com' 'Start the store: bash scripts/dev.sh'
