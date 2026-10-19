<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Services\CheckoutService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! app()->environment('testing') || ! str_contains(config('database.connections.sqlite.database'), 'mesh-stock-race-')) {
    throw new RuntimeException('This helper requires its isolated testing database.');
}
if (($argv[1] ?? '') === 'init') {
    StoreSetting::create(['id' => 1, 'delivery_enabled' => false]);
    $category = Category::create(['name' => 'Race test', 'slug' => 'race-test']);
    Product::create(['category_id' => $category->id, 'name' => 'Last three items', 'slug' => 'last-three', 'sku' => 'RACE-1', 'description' => 'Concurrency test fixture', 'price_cents' => 1000, 'stock_mode' => 'tracked', 'quantity' => 3, 'low_stock_threshold' => 1]);
    exit(0);
}
if (($argv[1] ?? '') === 'check') {
    echo json_encode(['quantity' => Product::firstOrFail()->quantity, 'orders' => Order::count(), 'ordered_units' => OrderItem::sum('quantity')]);
    exit(0);
}
if (getenv('MESH_RACE_READY_DIR') && preg_match('/^worker-[0-9]+$/', $argv[1] ?? '')) {
    file_put_contents(getenv('MESH_RACE_READY_DIR').'/'.$argv[1], 'ready');
    $deadline = microtime(true) + 20;
    while (! file_exists(getenv('MESH_RACE_START_FILE'))) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Timed out waiting for the checkout race start barrier.');
        }
        usleep(1000);
    }
}
try {
    app(CheckoutService::class)->place(['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Concurrent customer', 'phone' => '+96170123456', 'address' => 'Isolated test address'], [['product_id' => 1, 'color_id' => null, 'quantity' => 1]]);
    echo 'placed';
} catch (ValidationException $exception) {
    echo 'sold_out';
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
