<?php

// Continue an installed local checkout with one command, including on Windows.
chdir(dirname(__DIR__));
if (! is_file('vendor/autoload.php')) {
    fwrite(STDERR, "Install dependencies first: php scripts/setup.php\n");
    exit(1);
}
// A pull may add a locked dependency even when vendor already exists.
$lock = json_decode(file_get_contents('composer.lock'), true);
$installed = json_decode(file_get_contents('vendor/composer/installed.json'), true);
$versions = array_column($installed['packages'] ?? $installed, 'version', 'name');
$needsInstall = false;
foreach ($lock['packages'] ?? [] as $package) {
    if (($versions[$package['name']] ?? null) !== $package['version']) { $needsInstall = true; break; }
}
if ($needsInstall) {
    passthru('composer install --prefer-dist --no-interaction', $status);
    if ($status !== 0) exit($status);
}
require 'vendor/autoload.php';
Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
if (($_ENV['APP_ENV'] ?? '') !== 'local') {
    fwrite(STDERR, "This launcher requires APP_ENV=local. Use the production deployment guide for hosting.\n");
    exit(1);
}
// Paths cached on another disk must not survive local startup.
if (is_file('bootstrap/cache/config.php') && ! unlink('bootstrap/cache/config.php')) {
    throw new RuntimeException('Cannot clear the local configuration cache.');
}
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
if (! $app->environment('local')) {
    throw new RuntimeException('Local startup cannot run outside APP_ENV=local.');
}
$output = new Symfony\Component\Console\Output\ConsoleOutput;
$run = function (string $command, array $arguments = []) use ($kernel, $output): void {
    $status = $kernel->call($command, $arguments, $output);
    if ($status !== 0) {
        exit($status);
    }
};
$run('route:clear');
$run('view:clear');
$run('migrate', ['--force' => true]);
if (in_array('--demo', $argv, true) && ! App\Models\Product::exists()) {
    $run('db:seed', ['--class' => 'DemoCatalogSeeder', '--force' => true]);
}
if (! App\Models\User::where('role', 'owner')->exists()) {
    fwrite(STDOUT, 'Owner email: ');
    $email = trim(fgets(STDIN) ?: '');
    $run('shop:admin', ['email' => $email]);
}
$port = (int) (getenv('PORT') ?: 8000);
if ($port < 1024 || $port > 65535) {
    throw new RuntimeException('PORT must be between 1024 and 65535.');
}
echo 'Store: http://127.0.0.1:'.$port."\n";
echo 'Admin: http://127.0.0.1:'.$port.'/'.trim(config('shop.admin_path'), '/')."/login\n";
echo "Uploaded product images work with or without a public/storage link.\n";
$run('serve', ['--host' => '127.0.0.1', '--port' => $port, '--no-reload' => true]);
