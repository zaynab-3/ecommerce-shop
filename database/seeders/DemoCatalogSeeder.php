<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Demo data is only for local development.');
        }
        $this->call(DatabaseSeeder::class);
        $categories = ['Electronics' => 'headphones', 'Home & living' => 'home', 'Fashion' => 'bag', 'Beauty & care' => 'sparkles', 'Gaming' => 'gamepad', 'Accessories' => 'watch'];
        $roots = [];
        $position = 0;
        foreach ($categories as $name => $icon) {
            $roots[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'icon' => $icon, 'position' => $position++]);
        }
        $subs = [];
        foreach (['Audio' => 'Electronics', 'Computers & storage' => 'Electronics', 'Clothing' => 'Fashion', 'Bags' => 'Fashion', 'Jewelry' => 'Accessories', 'Workspace' => 'Home & living'] as $name => $parent) {
            $subs[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'parent_id' => $roots[$parent]->id, 'icon' => $roots[$parent]->icon]);
        }
        $products = [
            ['name' => 'Studio wireless headphones', 'category' => 'Audio', 'price' => 59, 'compare' => 79, 'image' => 'headphones-sand.svg', 'badge' => 'Best seller', 'qty' => 30, 'threshold' => 10, 'colors' => [['Sand', '#d9c9af', 'headphones-sand.svg'], ['Graphite', '#414640', 'headphones-graphite.svg'], ['Sage', '#a4ae96', 'headphones-sage.svg']]],
            ['name' => 'The everyday backpack', 'category' => 'Bags', 'price' => 49, 'compare' => 65, 'image' => 'backpack.webp', 'badge' => 'Everyday favorite', 'qty' => 10, 'threshold' => 5, 'colors' => [['Navy', '#293e55', null]]],
            ['name' => 'Ultra-wide curved monitor', 'category' => 'Computers & storage', 'price' => 199, 'compare' => 249, 'image' => 'monitor.webp', 'badge' => 'A good deal', 'qty' => 15, 'threshold' => 5, 'colors' => [['Black', '#252a27', null]]],
            ['name' => 'The everyday cotton tee', 'category' => 'Clothing', 'price' => 19, 'image' => 'tshirt.webp', 'badge' => 'New arrival', 'qty' => 100, 'threshold' => 15, 'colors' => [['Original', null, null]]],
            ['name' => 'Minimal ceramic table lamp', 'category' => 'Workspace', 'price' => 38, 'image' => 'lamp.svg', 'badge' => 'New arrival', 'qty' => 0, 'mode' => 'open', 'manual' => 'available', 'colors' => [['Warm white', '#ece5d5', null]]],
            ['name' => 'Portable solid-state drive', 'category' => 'Computers & storage', 'price' => 69, 'compare' => 89, 'image' => 'ssd.webp', 'qty' => 5, 'threshold' => 5],
            ['name' => 'The all-season jacket', 'category' => 'Clothing', 'price' => 54, 'image' => 'jacket.webp', 'qty' => 30, 'threshold' => 10, 'colors' => [['Khaki', '#b2a692', null]]],
            ['name' => 'Gold-tone chain bracelet', 'category' => 'Jewelry', 'price' => 29, 'image' => 'bracelet.webp', 'badge' => 'A little extra', 'qty' => 20, 'threshold' => 5],
            ['name' => 'Essential long-sleeve tee', 'category' => 'Clothing', 'price' => 24, 'image' => 'longsleeve.webp', 'qty' => 40, 'threshold' => 10],
            ['name' => 'Everyday silver ring', 'category' => 'Jewelry', 'price' => 22, 'image' => 'silver-ring.webp', 'qty' => 0, 'mode' => 'open', 'manual' => 'low'],
            ['name' => 'Portable external hard drive', 'category' => 'Computers & storage', 'price' => 45, 'image' => 'drive.webp', 'qty' => 0, 'threshold' => 5],
            ['name' => 'Lightweight rain jacket', 'category' => 'Clothing', 'price' => 42, 'image' => 'raincoat.webp', 'badge' => 'New arrival', 'qty' => 25, 'threshold' => 8],
        ];
        foreach ($products as $i => $data) {
            $sku = 'MESH-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $p = Product::firstOrCreate(['sku' => $sku], ['name' => $data['name'], 'slug' => Str::slug($data['name']), 'category_id' => $subs[$data['category']]->id, 'description' => 'A thoughtful upgrade for your everyday. '.$data['name'].' brings considered design and practical comfort to the things you use most. Explore the details, choose your favorite, and make a little more of your day. This is a demonstration product; replace its description and specifications before selling.', 'price_cents' => (int) ($data['price'] * 100), 'compare_price_cents' => isset($data['compare']) ? (int) ($data['compare'] * 100) : null, 'badge' => $data['badge'] ?? null, 'is_featured' => $i < 3, 'position' => $i, 'stock_mode' => $data['mode'] ?? 'tracked', 'quantity' => ($data['mode'] ?? 'tracked') === 'tracked' ? $data['qty'] : null, 'low_stock_threshold' => $data['threshold'] ?? 5, 'manual_stock_status' => $data['manual'] ?? 'available']);
            if (! $p->wasRecentlyCreated) {
                continue;
            }
            $p->images()->create(['path' => 'images/'.$data['image'], 'alt' => $data['name'], 'position' => 0]);
            foreach ($data['colors'] ?? [] as $j => [$name,$hex,$image]) {
                $color = $p->colors()->create(['name' => $name, 'hex' => $hex, 'position' => $j]);
                if ($image) {
                    $p->images()->create(['path' => 'images/'.$image, 'alt' => $data['name'].' in '.$name, 'product_color_id' => $color->id, 'position' => $j + 1]);
                }
            }
        }
        foreach (['Beirut' => 3, 'Nabatieh' => 5, 'Saida' => 4, 'Jounieh' => 4, 'Tyre' => 5, 'Tripoli' => 5, 'Zahle' => 5] as $name => $fee) {
            DeliveryZone::where('name', $name)->where('is_active', false)->where('fee_cents', 0)->update(['is_active' => true, 'fee_cents' => $fee * 100]);
        }
    }
}
