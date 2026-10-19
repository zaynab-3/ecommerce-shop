<?php

use Illuminate\Contracts\Console\Kernel;

// Portable setup entry point: php scripts/setup.php (PowerShell, Bash, macOS).
chdir(dirname(__DIR__));

if (PHP_VERSION_ID < 80200) {
    fwrite(STDERR, "PHP 8.2 or newer is required.\n");
    exit(1);
}

$missing = array_values(array_filter(
    ['ctype', 'dom', 'fileinfo', 'gd', 'mbstring', 'pdo', 'xml', 'xmlwriter'],
    fn ($extension) => ! extension_loaded($extension)
));
if ($missing || ! function_exists('imagewebp')) {
    fwrite(STDERR, 'Enable the required PHP extensions: '.implode(', ', $missing)."; GD must support WebP.\nRun php --ini to locate the active php.ini.\n");
    exit(1);
}

passthru('composer install --prefer-dist --no-interaction', $installStatus);
if ($installStatus !== 0) {
    exit($installStatus);
}

if (! file_exists('.env')) {
    if (! copy('.env.example', '.env')) {
        throw new RuntimeException('Could not create .env. Check directory permissions.');
    }
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod('.env', 0600);
    }
}

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$run = function (string $command, array $arguments = []) use ($kernel): void {
    $status = $kernel->call($command, $arguments);
    echo $kernel->output();
    if ($status !== 0) {
        exit($status);
    }
};

if (! config('app.key')) {
    $run('key:generate', ['--force' => true]);
}
if (config('database.default') === 'sqlite') {
    if (! extension_loaded('pdo_sqlite')) {
        fwrite(STDERR, "Enable pdo_sqlite in your active php.ini, then rerun setup.\n");
        exit(1);
    }
    $database = config('database.connections.sqlite.database');
    if ($database !== ':memory:' && ! file_exists($database) && ! touch($database)) {
        throw new RuntimeException('Could not create the SQLite database.');
    }
}

$run('migrate', ['--force' => true]);
$run('db:seed', ['--force' => true]);
if (! file_exists('public/storage') && ! is_link('public/storage')) {
    $run('storage:link');
}
$run('view:cache');

echo "Setup complete.\n";
echo "Demo products (optional): php artisan db:seed --class=DemoCatalogSeeder\n";
echo "Create an owner: php artisan shop:admin you@example.com\n";
echo "Start the store: php artisan serve\n";
