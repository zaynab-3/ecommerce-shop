<?php

// Prepare private configuration for the bundled local MySQL/phpMyAdmin services.
// Requires PHP only; run before Composer installation on a fresh checkout.
chdir(dirname(__DIR__));

$text = file_exists('.env') ? file_get_contents('.env') : file_get_contents('.env.example');
$get = function (string $key) use (&$text): string {
    preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $text, $matches);

    return trim($matches[1] ?? '', " \t\r\n\"'");
};
$set = function (string $key, string $value) use (&$text): void {
    $line = $key.'='.$value;
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
    $text = preg_match($pattern, $text) ? preg_replace_callback($pattern, fn () => $line, $text) : rtrim($text)."\n".$line."\n";
};
if ($get('APP_ENV') !== 'local' || $get('DB_CONNECTION') !== 'mysql'
    || ! in_array($get('DB_HOST'), ['127.0.0.1', 'localhost'], true)
    || ! preg_match('/^[a-zA-Z0-9_]{1,48}$/D', $get('DB_DATABASE'))
    || ! preg_match('/^[a-zA-Z0-9_]{1,32}$/D', $get('DB_USERNAME'))
    || $get('DB_USERNAME') === 'root' || $get('DB_URL') !== '') {
    fwrite(STDERR, "Local Docker setup requires APP_ENV=local, MySQL on localhost, a simple database name, and a non-root user. Existing configuration was preserved. For your own MySQL server, follow docs/WINDOWS-SETUP.md instead.\n");
    exit(1);
}
foreach (['DB_PASSWORD', 'MYSQL_ROOT_PASSWORD'] as $key) {
    if ($get($key) === '') {
        $set($key, bin2hex(random_bytes(24)));
    }
}
if ($get('APP_KEY') === '') {
    $set('APP_KEY', 'base64:'.base64_encode(random_bytes(32)));
}
if (file_put_contents('.env', $text) === false) {
    throw new RuntimeException('Cannot write local .env. Check directory permissions.');
}
if (PHP_OS_FAMILY !== 'Windows') {
    chmod('.env', 0600);
}
if (! file_exists('.env.testing')) {
    $testing = file_get_contents('.env.testing.example');
    foreach (['APP_KEY', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
        // Copy the exact dotenv assignment to preserve password quoting.
        preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $text, $matches);
        $value = $key === 'DB_DATABASE' ? $get($key).'_test' : ($matches[1] ?? '');
        $testing = preg_replace_callback('/^'.preg_quote($key, '/').'=.*$/m', fn () => $key.'='.$value, $testing);
    }
    if (file_put_contents('.env.testing', $testing) === false) {
        throw new RuntimeException('Cannot write .env.testing. Check directory permissions.');
    }
    if (PHP_OS_FAMILY !== 'Windows') {
        chmod('.env.testing', 0600);
    }
}
echo "Private local .env and .env.testing prepared; existing passwords, key, and test configuration preserved.\n";
echo "Next: docker compose up -d --wait, then php scripts/setup.php.\n";
echo "phpMyAdmin uses DB_USERNAME and DB_PASSWORD from your private .env (do not share them).\n";
