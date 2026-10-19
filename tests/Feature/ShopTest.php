<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CheckoutService;
use App\Services\ImageService;
use App\Services\OrderStatusService;
use App\Services\StoreTheme;
use App\Services\WhatsAppOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        StoreSetting::create(['id' => 1, 'delivery_enabled' => true]);
    }

    private function product(array $attrs = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'tech'], ['name' => 'Tech']);

        return Product::create([...['category_id' => $category->id, 'name' => 'A good product', 'slug' => 'product-'.Str::random(8), 'sku' => Str::random(10), 'description' => 'A useful product description.', 'price_cents' => 2500, 'stock_mode' => 'tracked', 'quantity' => 10, 'low_stock_threshold' => 5, 'is_active' => true], ...$attrs]);
    }

    private function admin(string $role = 'owner'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function data(): array
    {
        $zone = DeliveryZone::firstOrCreate(['name' => 'Beirut'], ['governorate' => 'Beirut', 'fee_cents' => 300, 'is_active' => true]);

        return ['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Test Customer', 'phone' => '+96170123456', 'address' => 'Beirut, Hamra Street, Building 10', 'delivery_zone_id' => $zone->id];
    }

    private function cart(Product $p, int $quantity = 1, ?int $color = null): array
    {
        return [$p->id.'-'.($color ?? 'none') => ['product_id' => $p->id, 'color_id' => $color, 'quantity' => $quantity]];
    }

    public function test_storefront_and_all_public_pages_render(): void
    {
        $p = $this->product();
        foreach (['home', 'shop', 'cart.index', 'help'] as $route) {
            $this->get(route($route))->assertOk();
        }$this->get(route('products.show', $p))->assertOk()->assertSee($p->name);
        $this->get('/checkout')->assertRedirect(route('cart.index'));
    }

    public function test_buy_now_preserves_existing_cart_and_validates_color_and_stock(): void
    {
        $existing = $this->product();
        $p = $this->product(['quantity' => 3]);
        $color = $p->colors()->create(['name' => 'Black']);
        $this->withSession(['cart' => $this->cart($existing)])
            ->post(route('cart.add', $p), ['quantity' => 2, 'color_id' => $color->id, 'buy_now' => 1])
            ->assertRedirect(route('checkout'))
            ->assertSessionHas('cart.'.$existing->id.'-none.quantity', 1)
            ->assertSessionHas('cart.'.$p->id.'-'.$color->id.'.quantity', 2);
        $this->post(route('cart.add', $p), ['quantity' => 1, 'buy_now' => 1])->assertSessionHasErrors('color_id');
        $this->post(route('cart.add', $p), ['quantity' => 2, 'color_id' => $color->id, 'buy_now' => 1])->assertSessionHasErrors('quantity');
        $this->assertSame(3, $p->fresh()->quantity);
    }

    public function test_card_quick_add_supports_products_without_colors_and_with_a_single_color(): void
    {
        $plain = $this->product();
        $single = $this->product();
        $color = $single->colors()->create(['name' => 'Natural']);
        $multiple = $this->product();
        $multiple->colors()->createMany([['name' => 'Red'], ['name' => 'Black']]);
        $this->get(route('shop'))->assertOk()->assertSee('Choose options')->assertSee('Add to cart');
        $this->post(route('cart.add', $plain), ['quantity' => 1])->assertRedirect()
            ->assertSessionHas('cart.'.$plain->id.'-none.quantity', 1);
        $this->post(route('cart.add', $single), ['quantity' => 1, 'color_id' => $color->id])->assertRedirect()
            ->assertSessionHas('cart.'.$single->id.'-'.$color->id.'.quantity', 1);
        $this->post(route('cart.add', $multiple), ['quantity' => 1])->assertSessionHasErrors('color_id');
    }

    public function test_owner_can_change_website_color_and_invalid_css_is_rejected(): void
    {
        StoreSetting::current(); // Populate the cache before changing the setting.
        $settings = ['store_name' => 'MESH', 'delivery_enabled' => 1, 'whatsapp_enabled' => 0, 'accent_color' => '#a12465'];
        $this->actingAs($this->admin())->patch(route('admin.settings.update'), $settings)->assertSessionHasNoErrors();
        $this->assertSame('#a12465', StoreSetting::current()->accentColor());
        $this->get(route('shop'))->assertOk()->assertSee('/theme/a12465.css', false);
        foreach (['red', '#123', '#ffffff;display:none', '</style><script>1</script>'] as $invalid) {
            $this->patch(route('admin.settings.update'), [...$settings, 'accent_color' => $invalid])->assertSessionHasErrors('accent_color');
        }
        $this->assertSame('#a12465', StoreSetting::first()->accent_color);
    }

    public function test_theme_css_is_public_cacheable_and_handles_light_colors_with_readable_text(): void
    {
        $response = $this->get('/theme/ffff00.css')->assertOk()
            ->assertHeader('Content-Type', 'text/css; charset=UTF-8')->assertHeaderMissing('Set-Cookie')
            ->assertSee('--store-accent:#ffff00;', false)->assertSee('--store-on-accent:#000000;', false);
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->get('/theme/not-a-color.css')->assertNotFound();
        foreach (['#ffff00', '#ffffff', '#000000', '#3868e9', '#ff77ff'] as $color) {
            $vars = StoreTheme::variables($color);
            $luminance = static function ($hex) {
                $rgb = array_map(fn ($c) => hexdec($c) / 255, str_split(substr($hex, 1), 2));
                $rgb = array_map(fn ($c) => $c <= .04045 ? $c / 12.92 : (($c + .055) / 1.055) ** 2.4, $rgb);
                return .2126 * $rgb[0] + .7152 * $rgb[1] + .0722 * $rgb[2];
            };
            $this->assertGreaterThanOrEqual(4.5, 1.05 / ($luminance($vars['--store-accent-ink']) + .05));
            $a = $luminance($color); $b = $luminance($vars['--store-on-accent']);
            $this->assertGreaterThanOrEqual(4.5, (max($a, $b) + .05) / (min($a, $b) + .05));
        }
    }

    public function test_product_whatsapp_enquiry_uses_the_store_phone_and_respects_the_switch(): void
    {
        $p = $this->product(['name' => 'Desk & lamp?']);
        $this->get(route('products.show', $p))->assertDontSee('Ask about this product');
        StoreSetting::first()->update(['whatsapp_enabled' => true, 'whatsapp_recipient' => '+961 70 123 456']);
        Cache::forget('store.settings');
        $url = app(WhatsAppOrderService::class)->productUrl($p);
        $this->assertStringStartsWith('https://wa.me/96170123456?text=', $url);
        $this->assertStringContainsString($p->name, rawurldecode($url));
        $this->assertStringContainsString(route('products.show', $p), rawurldecode($url));
        $this->get(route('products.show', $p))->assertSee('Ask about this product')->assertSee(e($url), false);
    }

    public function test_uploaded_images_and_thumbnails_are_accessible_without_a_storage_link(): void
    {
        Storage::fake('public');
        $path = app(ImageService::class)->store(UploadedFile::fake()->image('photo.jpg', 600, 400));
        foreach ([$path, str_replace('.webp', '-480.webp', $path)] as $file) {
            $response = $this->get('/storage/'.$file)->assertOk()
                ->assertHeader('Content-Type', 'image/webp')
                ->assertHeaderMissing('Set-Cookie')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
            $this->assertSame(Storage::disk('public')->get($file), file_get_contents($response->baseResponse->getFile()->getPathname()));
        }
    }

    public function test_image_fallback_does_not_expose_other_files_or_missing_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('private.txt', 'Do not expose this file');
        foreach (['/storage/private.txt', '/storage/products/private.txt', '/storage/products/'.Str::uuid().'.webp', '/storage/products/..%2F..%2F.env'] as $url) {
            $this->get($url)->assertNotFound()->assertDontSee('Do not expose this file');
        }
    }

    public function test_categories_inherit_parent_visibility_and_checkout_rechecks_it(): void
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent', 'is_active' => false]);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);
        $p = $this->product(['category_id' => $child->id]);
        $this->get(route('products.show', $p))->assertNotFound();
        $this->get('/shop')->assertDontSee($p->name);
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->place($this->data(), $this->cart($p));
    }

    public function test_stock_thresholds_cover_the_requested_examples(): void
    {
        foreach ([[10, 5], [30, 10], [100, 15], [200, 30]] as [$qty,$threshold]) {
            $this->assertSame($threshold, Product::defaultThreshold($qty));
            $p = $this->product(['quantity' => $qty, 'low_stock_threshold' => $threshold]);
            $this->assertSame('available', $p->stock_status);
            $p->quantity = $threshold;
            $this->assertSame('low', $p->stock_status);
            $p->quantity = 0;
            $this->assertSame('out', $p->stock_status);
        }
    }

    public function test_open_stock_uses_manual_status_and_is_not_decremented(): void
    {
        $p = $this->product(['stock_mode' => 'open', 'quantity' => null, 'manual_stock_status' => 'low']);
        $order = app(CheckoutService::class)->place($this->data(), $this->cart($p, 25));
        $this->assertSame('low', $p->fresh()->stock_status);
        $this->assertNull($p->fresh()->quantity);
        $this->assertSame(62500, $order->subtotal_cents);
    }

    public function test_checkout_saves_snapshots_uses_server_prices_and_decrements_stock(): void
    {
        $p = $this->product();
        $data = $this->data();
        $data['price_cents'] = 1;
        $order = app(CheckoutService::class)->place($data, $this->cart($p, 2));
        $this->assertSame(8, $p->fresh()->quantity);
        $this->assertEquals(5300, $order->total_cents);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'name' => $p->name, 'price_cents' => 2500, 'quantity' => 2]);
    }

    public function test_same_checkout_key_returns_one_order_without_double_stock_deduction(): void
    {
        $p = $this->product();
        $data = $this->data();
        $first = app(CheckoutService::class)->place($data, $this->cart($p, 3));
        $second = app(CheckoutService::class)->place($data, $this->cart($p, 3));
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Order::count());
        $this->assertSame(7, $p->fresh()->quantity);
    }

    public function test_insufficient_stock_rolls_back_the_entire_order(): void
    {
        $p = $this->product(['quantity' => 1]);
        try {
            app(CheckoutService::class)->place($this->data(), $this->cart($p, 2));
            $this->fail('Overselling was allowed');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cart', $e->errors());
        }$this->assertSame(1, $p->fresh()->quantity);
        $this->assertSame(0, Order::count());
    }

    public function test_stock_is_aggregated_across_colors_before_deduction(): void
    {
        $p = $this->product(['quantity' => 3]);
        $a = $p->colors()->create(['name' => 'Black']);
        $b = $p->colors()->create(['name' => 'White']);
        $cart = [...$this->cart($p, 2, $a->id), ...$this->cart($p, 2, $b->id)];
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->place($this->data(), $cart);
    }

    public function test_color_from_another_product_cannot_be_added(): void
    {
        $p = $this->product();
        $other = $this->product();
        $color = $other->colors()->create(['name' => 'Foreign']);
        $this->post(route('cart.add', $p), ['quantity' => 1, 'color_id' => $color->id])->assertSessionHasErrors('color_id');
    }

    public function test_color_selection_and_unassigned_images_render(): void
    {
        $p = $this->product();
        $color = $p->colors()->create(['name' => 'No hex']);
        $p->images()->create(['path' => 'images/placeholder.svg', 'alt' => 'Common', 'position' => 0]);
        $p->images()->create(['path' => 'images/headphones-sage.svg', 'alt' => 'Sage', 'product_color_id' => $color->id, 'position' => 1]);
        $this->get(route('products.show', $p))->assertOk()->assertSee('data-color-image=', false)->assertSee('headphones-sage.svg')->assertSee('No hex');
    }

    public function test_disabled_delivery_adds_no_fee_and_ignores_submitted_fee(): void
    {
        StoreSetting::first()->update(['delivery_enabled' => false]);
        $p = $this->product();
        $data = $this->data();
        $data['delivery_cents'] = 999999;
        $order = app(CheckoutService::class)->place($data, $this->cart($p));
        $this->assertEquals(0, $order->delivery_cents);
        $this->assertEquals(2500, $order->total_cents);
    }

    public function test_inactive_delivery_zone_is_rejected(): void
    {
        $p = $this->product();
        $data = $this->data();
        DeliveryZone::query()->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->place($data, $this->cart($p));
    }

    public function test_cancellation_restores_stock_exactly_once_and_cannot_be_reopened(): void
    {
        $p = $this->product();
        $order = app(CheckoutService::class)->place($this->data(), $this->cart($p, 2));
        $service = app(OrderStatusService::class);
        $service->update($order, 'cancelled', null);
        $service->update($order->fresh(), 'cancelled', null);
        $this->assertSame(10, $p->fresh()->quantity);
        $this->expectException(ValidationException::class);
        $service->update($order->fresh(), 'confirmed', null);
    }

    public function test_order_confirmation_is_private_to_checkout_session(): void
    {
        $p = $this->product();
        $order = app(CheckoutService::class)->place($this->data(), $this->cart($p));
        $url = route('orders.confirmation', $order->public_id);
        $this->get($url)->assertNotFound();
        $this->withSession(['order_access' => [$order->public_id]])->get($url)->assertOk()->assertSee($order->number);
    }

    public function test_guest_checkout_flow_and_session_idempotency(): void
    {
        $p = $this->product();
        $data = $this->data();
        $this->withSession(['cart' => $this->cart($p), 'checkout_key' => $data['idempotency_key']])->post('/checkout', $data)->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertEquals(2800, $order->total_cents);
        $this->get(route('orders.confirmation', $order->public_id))->assertOk();
        $this->post('/checkout', $data)->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_public_auth_routes_are_absent_and_admin_routes_require_login(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/login')->assertNotFound();
        $this->get('/manage-store/products')->assertRedirect(route('admin.login'));
        $this->get('/manage-store/login')->assertOk()->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_order_agent_cannot_manage_catalog_settings_or_team(): void
    {
        $this->actingAs($this->admin('orders'));
        foreach (['/manage-store/products', '/manage-store/categories', '/manage-store/delivery', '/manage-store/settings', '/manage-store/team'] as $url) {
            $this->get($url)->assertForbidden();
        }$this->get('/manage-store/orders')->assertOk();
    }

    public function test_inactive_admin_is_blocked_even_with_an_existing_session(): void
    {
        $user = $this->admin();
        $user->update(['is_active' => false]);
        $this->actingAs($user)->get('/manage-store')->assertForbidden();
    }

    public function test_order_agent_can_claim_but_cannot_steal_an_order(): void
    {
        $p = $this->product();
        $order = app(CheckoutService::class)->place($this->data(), $this->cart($p));
        $agent = $this->admin('orders');
        $other = $this->admin('orders');
        $this->actingAs($agent)->patch(route('admin.orders.update', $order), ['status' => 'confirmed', 'assigned_to' => $agent->id])->assertRedirect();
        $this->actingAs($other)->patch(route('admin.orders.update', $order), ['status' => 'processing', 'assigned_to' => $other->id])->assertForbidden();
    }

    public function test_all_admin_screens_render_with_eager_loaded_relationships(): void
    {
        $p = $this->product();
        $order = app(CheckoutService::class)->place($this->data(), $this->cart($p));
        $this->actingAs($this->admin());
        foreach (['/manage-store', '/manage-store/products', '/manage-store/products/create', '/manage-store/categories', '/manage-store/orders', '/manage-store/delivery', '/manage-store/settings', '/manage-store/team', route('admin.products.edit', $p), route('admin.orders.show', $order)] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_quick_stock_availability_and_open_status_actions(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin());
        $this->patch(route('admin.products.quick', $p), ['delta' => 5, 'is_active' => 0])->assertRedirect();
        $this->assertSame(15, $p->fresh()->quantity);
        $this->assertFalse($p->fresh()->is_active);
        $this->patch(route('admin.products.quick', $p), ['delta' => -99])->assertUnprocessable();
        $p->update(['stock_mode' => 'open', 'quantity' => null]);
        $this->patch(route('admin.products.quick', $p), ['manual_stock_status' => 'low'])->assertRedirect();
        $this->assertSame('low', $p->fresh()->stock_status);
    }

    public function test_bulk_delivery_sets_only_selected_areas(): void
    {
        $a = DeliveryZone::create(['name' => 'Beirut', 'governorate' => 'Beirut']);
        $b = DeliveryZone::create(['name' => 'Saida', 'governorate' => 'South']);
        $this->actingAs($this->admin())->patch(route('admin.delivery.update'), ['zones' => [$b->id], 'fee' => '4.50', 'is_active' => 1])->assertRedirect();
        $this->assertFalse($a->fresh()->is_active);
        $this->assertTrue($b->fresh()->is_active);
        $this->assertEquals(450, $b->fresh()->fee_cents);
    }

    public function test_webp_processing_resizes_and_rejects_script_files(): void
    {
        Storage::fake('public');
        $path = app(ImageService::class)->store(UploadedFile::fake()->image('large.png', 2400, 1200));
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists(str_replace('.webp', '-480.webp', $path));
        $info = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(1600, $info[0]);
        $this->assertSame(800, $info[1]);
        $this->actingAs($this->admin())->post(route('admin.products.store'), ['images' => [UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')]])->assertSessionHasErrors('images.0');
    }

    public function test_product_creation_and_color_image_ownership(): void
    {
        $this->actingAs($this->admin());
        $category = Category::create(['name' => 'Home', 'slug' => 'home']);
        $this->post(route('admin.products.store'), ['name' => 'New item', 'sku' => 'SKU-1', 'category_id' => $category->id, 'description' => 'A lovely useful item.', 'price' => '25.50', 'stock_mode' => 'tracked', 'quantity' => 30, 'manual_stock_status' => 'available', 'is_active' => 1, 'colors' => [['name' => 'Unassigned swatch', 'hex' => null]]])->assertRedirect();
        $p = Product::where('sku', 'SKU-1')->firstOrFail();
        $this->assertEquals(2550, $p->price_cents);
        $this->assertEquals(10, $p->low_stock_threshold);
        $image = $p->images()->create(['path' => 'images/placeholder.svg', 'alt' => 'Image']);
        $other = $this->product();
        $foreign = $other->colors()->create(['name' => 'Foreign']);
        $this->patch(route('admin.products.image', [$p, $image]), ['position' => 0, 'product_color_id' => $foreign->id])->assertUnprocessable();
        $this->patch(route('admin.products.image', [$p, $image]), ['position' => 1, 'product_color_id' => $p->colors()->first()->id])->assertRedirect();
    }

    public function test_output_is_escaped_and_security_headers_are_applied(): void
    {
        $p = $this->product(['name' => '<script>alert(1)</script>']);
        $this->get(route('products.show', $p))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
        $this->get('/shop?q='.urlencode("' OR 1=1 --"))->assertOk()->assertDontSee($p->name);
    }

    public function test_catalog_queries_do_not_grow_per_product(): void
    {
        $this->product();
        $run = function () {
            Cache::flush();
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get('/shop')->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };
        $one = $run();
        for ($i = 0; $i < 10; $i++) {
            $this->product();
        }$many = $run();
        $this->assertLessThanOrEqual($one + 1, $many, 'Product queries grew with catalog size.');
    }

    public function test_stock_endpoint_has_bounded_payload_and_live_results(): void
    {
        $p = $this->product(['quantity' => 3]);
        $this->getJson('/stock?ids[]='.$p->id)->assertOk()->assertJsonFragment(['status' => 'low', 'quantity' => 3]);
        $this->getJson('/stock?'.http_build_query(['ids' => range(1, 49)]))->assertUnprocessable();
    }

    public function test_whatsapp_can_be_enabled_with_only_a_phone_number(): void
    {
        $this->actingAs($this->admin())->patch(route('admin.settings.update'), [
            'store_name' => 'MESH', 'delivery_enabled' => 1, 'whatsapp_enabled' => 1, 'whatsapp_recipient' => '+961 (70) 123-456',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(StoreSetting::first()->whatsapp_enabled);
        $this->assertSame('96170123456', StoreSetting::first()->whatsapp_recipient);
        $this->get(route('admin.settings.index'))->assertOk()->assertDontSee('WHATSAPP_TOKEN')->assertDontSee('API credentials');
    }

    public function test_whatsapp_requires_a_valid_international_number_when_enabled(): void
    {
        $this->actingAs($this->admin());
        foreach (['', 'javascript:alert(1)', '96170abc123456', '070123456', '96170123456789012345'] as $phone) {
            $this->patch(route('admin.settings.update'), ['store_name' => 'MESH', 'delivery_enabled' => 1, 'whatsapp_enabled' => 1, 'whatsapp_recipient' => $phone])
                ->assertSessionHasErrors('whatsapp_recipient');
        }
        $this->assertFalse(StoreSetting::first()->whatsapp_enabled);
        $this->assertSame('96170123456', WhatsAppOrderService::normalizePhone('00961 70 123 456'));
    }

    public function test_whatsapp_message_contains_the_saved_cart_and_safely_encodes_text(): void
    {
        $p = $this->product();
        $color = $p->colors()->create(['name' => 'Sand & Sage']);
        $data = $this->data();
        $data['customer_name'] = 'زينب';
        $data['email'] = 'buyer@example.com';
        $data['notes'] = '2+1=3 &text=keep every detail';
        $order = app(CheckoutService::class)->place($data, $this->cart($p, 2, $color->id));
        $p->update(['name' => 'Changed later', 'price_cents' => 9999]);
        StoreSetting::first()->update(['whatsapp_enabled' => true, 'whatsapp_recipient' => '96170123456']);
        Cache::forget('store.settings');
        $url = app(WhatsAppOrderService::class)->url($order);
        $this->assertSame('wa.me', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/96170123456', parse_url($url, PHP_URL_PATH));
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame(['text'], array_keys($query));
        foreach ([$order->number, $p->sku, 'A good product', 'Sand & Sage', '2 x $25.00 USD = $50.00 USD', 'زينب', 'buyer@example.com', $data['phone'], $data['address'], $data['notes'], 'Area: Beirut', 'Subtotal: $50.00 USD', 'Delivery: $3.00 USD', 'Total: $53.00 USD', 'Payment: Cash on delivery'] as $text) {
            $this->assertStringContainsString($text, $query['text']);
        }
        $this->assertStringNotContainsString('Changed later', $query['text']);
    }

    public function test_checkout_hands_off_to_whatsapp_once_after_saving_the_order(): void
    {
        Queue::fake();
        $p = $this->product();
        $data = $this->data();
        StoreSetting::first()->update(['whatsapp_enabled' => true, 'whatsapp_recipient' => '96170123456']);
        Cache::forget('store.settings');
        $this->withSession(['cart' => $this->cart($p, 2), 'checkout_key' => $data['idempotency_key']])
            ->post('/checkout', $data)->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame(8, $p->fresh()->quantity);
        $url = route('orders.confirmation', $order->public_id);
        $this->get($url)->assertOk()->assertSee('data-whatsapp-auto-open', false)->assertSee('https://wa.me/96170123456?text=', false)->assertSee('Your order is saved.')->assertSee('Send');
        $this->get($url)->assertOk()->assertSee('data-whatsapp-order', false)->assertDontSee('data-whatsapp-auto-open', false);
        Queue::assertNothingPushed();
    }

    public function test_whatsapp_disabled_keeps_the_normal_checkout_without_a_handoff(): void
    {
        $p = $this->product();
        $data = $this->data();
        $this->withSession(['cart' => $this->cart($p), 'checkout_key' => $data['idempotency_key']])->post('/checkout', $data)->assertRedirect();
        $order = Order::firstOrFail();
        $this->get(route('orders.confirmation', $order->public_id))->assertOk()->assertDontSee('data-whatsapp-order', false)->assertDontSee('data-whatsapp-auto-open', false);
        $this->assertNull(app(WhatsAppOrderService::class)->url($order));
        StoreSetting::first()->update(['whatsapp_enabled' => true, 'whatsapp_recipient' => 'invalid']);
        Cache::forget('store.settings');
        $this->assertNull(app(WhatsAppOrderService::class)->url($order));
    }

    public function test_whatsapp_does_not_truncate_large_carts(): void
    {
        $cart = [];
        for ($i = 0; $i < 50; $i++) {
            $p = $this->product(['name' => 'Product '.$i.' '.str_repeat('details ', 15)]);
            $cart = [...$cart, ...$this->cart($p)];
        }
        $data = $this->data();
        $data['notes'] = str_repeat('Delivery note ', 60).'END OF NOTES';
        $order = app(CheckoutService::class)->place($data, $cart);
        $message = app(WhatsAppOrderService::class)->message($order);
        $this->assertStringContainsString('Product 0 ', $message);
        $this->assertStringContainsString('50. Product 49 ', $message);
        $this->assertStringContainsString('END OF NOTES', $message);
        $this->assertStringContainsString('Total: $1,253.00 USD', $message);
    }

    public function test_owner_cannot_remove_their_own_access(): void
    {
        $owner = $this->admin();
        $this->actingAs($owner)->patch(route('admin.team.update', $owner), ['role' => 'orders', 'is_active' => 1])->assertUnprocessable();
        $this->assertSame('owner', $owner->fresh()->role);
    }

    public function test_reordering_is_scoped_and_persisted(): void
    {
        $a = $this->product(['position' => 0]);
        $b = $this->product(['position' => 1]);
        $this->actingAs($this->admin())->post(route('admin.sort', ['products', $b->id]), ['direction' => 'up'])->assertRedirect();
        $this->assertLessThan($a->fresh()->position, $b->fresh()->position);
    }

    public function test_login_attempts_are_throttled(): void
    {
        $this->admin();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/manage-store/login', ['email' => 'wrong@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }$this->post('/manage-store/login', ['email' => 'wrong@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_stale_admin_editor_cannot_overwrite_stock_after_checkout(): void
    {
        $p = $this->product();
        $revision = $p->stock_revision ?? 0;
        app(CheckoutService::class)->place($this->data(), $this->cart($p, 2));
        $this->actingAs($this->admin())->put(route('admin.products.update', $p), [
            'stock_revision' => $revision, 'name' => $p->name, 'sku' => $p->sku, 'category_id' => $p->category_id,
            'description' => $p->description, 'price' => '25.00', 'stock_mode' => 'tracked', 'quantity' => 10,
            'manual_stock_status' => 'available', 'is_active' => 1,
        ])->assertSessionHasErrors('quantity');
        $this->assertSame(8, $p->fresh()->quantity);
    }

    public function test_cart_delivery_estimate_is_carried_to_checkout(): void
    {
        $p = $this->product();
        $data = $this->data();
        $this->withSession(['cart' => $this->cart($p)])->get('/cart?delivery_zone_id='.$data['delivery_zone_id'])
            ->assertOk()->assertSee('$28.00')->assertSessionHas('delivery_zone_id', $data['delivery_zone_id']);
        $this->get('/checkout')->assertOk();
    }

    public function test_state_changes_require_csrf_outside_the_testing_bypass(): void
    {
        $product = $this->product();
        $owner = $this->admin();
        $this->app->instance('env', 'local');
        try {
            $this->post(route('cart.add', $product), ['quantity' => 1])->assertStatus(419);
            $this->post('/manage-store/login', ['email' => $owner->email, 'password' => 'password'])
                ->assertRedirect(route('admin.login'))->assertSessionHasErrors('email')
                ->assertSessionHas('_old_input.email', $owner->email)
                ->assertSessionMissing('_old_input.password');
            $this->assertGuest();
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_cross_origin_requests_do_not_receive_cors_access(): void
    {
        $product = $this->product();
        $this->withHeaders(['Origin' => 'https://untrusted.example'])->getJson('/stock?ids[]='.$product->id)
            ->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_a_customer_can_place_two_distinct_orders_in_the_same_session(): void
    {
        $p = $this->product();
        $data = $this->data();
        $this->withSession(['cart' => $this->cart($p), 'checkout_key' => $data['idempotency_key']])->post('/checkout', $data)->assertRedirect();
        $firstKey = $data['idempotency_key'];
        $this->post(route('cart.add', $p), ['quantity' => '2'])->assertRedirect();
        $this->get('/checkout')->assertOk();
        $data['idempotency_key'] = session('checkout_key');
        $this->assertNotSame($firstKey, $data['idempotency_key']);
        $this->post('/checkout', $data)->assertRedirect();
        $this->assertSame(2, Order::count());
        $this->assertSame(7, $p->fresh()->quantity);
        $this->assertSame(2, Order::latest('id')->first()->items()->first()->quantity);
    }

    public function test_the_remove_button_accepts_a_browser_form_zero(): void
    {
        $p = $this->product();
        $cart = $this->cart($p);
        $this->withSession(['cart' => $cart])->patch(route('cart.update', array_key_first($cart)), ['quantity' => '0'])
            ->assertRedirect()->assertSessionHas('cart', []);
        $this->get('/checkout')->assertRedirect(route('cart.index'));
    }

    public function test_checkout_rejects_zero_quantity_from_an_existing_cart(): void
    {
        $p = $this->product();
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->place($this->data(), $this->cart($p, 0));
    }

    public function test_checkout_requires_a_color_if_options_were_added_after_the_cart(): void
    {
        $p = $this->product();
        $cart = $this->cart($p);
        $p->colors()->create(['name' => 'New color']);
        $this->expectException(ValidationException::class);
        app(CheckoutService::class)->place($this->data(), $cart);
    }

    public function test_catalog_search_treats_sql_wildcards_as_literal_text(): void
    {
        $special = $this->product(['name' => '100% Cotton_A!']);
        $other = $this->product(['name' => '100 percent Cotton AA']);
        foreach (['100%', 'Cotton_A', 'A!'] as $term) {
            $this->get('/shop?q='.urlencode($term))->assertOk()->assertSee($special->name)->assertDontSee($other->name);
        }
    }
}
