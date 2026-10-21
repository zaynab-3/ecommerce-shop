<?php

// Creates an owner only in the fresh, disposable GitHub Actions preview database.
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

function refusePreviewOwner(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

if (getenv('GITHUB_ACTIONS') !== 'true' || ! getenv('RUNNER_TEMP')) {
    refusePreviewOwner('Demo-owner creation requires the isolated preview workflow.');
}
$pem = base64_decode(getenv('PREVIEW_ADMIN_PUBLIC_KEY') ?: '', true);
$key = $pem ? openssl_pkey_get_public($pem) : false;
$details = $key ? openssl_pkey_get_details($key) : false;
if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
    refusePreviewOwner('Provide an RSA public key of at least 2048 bits.');
}
try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    if (! $app->environment('local') || config('database.default') !== 'mysql'
        || config('database.connections.mysql.database') !== 'mesh_owner_preview'
        || User::exists()) {
        refusePreviewOwner('Refusing to create a demo owner outside a fresh preview database.');
    }
    $password = 'M3sh!'.bin2hex(random_bytes(12));
    echo '::add-mask::'.$password.PHP_EOL;
    $email = 'owner@mesh-preview.test';
    User::create(['name' => 'Owner preview', 'email' => $email, 'password' => $password, 'role' => 'owner', 'is_active' => true]);
    $directory = rtrim(getenv('RUNNER_TEMP'), '/');
    if (file_put_contents($directory.'/mesh-preview-admin-public.pem', $pem) === false
        || file_put_contents($directory.'/mesh-preview-admin.json', json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR)) === false) {
        refusePreviewOwner('Cannot write the private preview credential files.');
    }
    chmod($directory.'/mesh-preview-admin.json', 0600);
    echo 'Created a separate owner for the disposable preview.'.PHP_EOL;
} catch (Throwable $exception) {
    refusePreviewOwner('Could not prepare the owner in the isolated preview.');
}
