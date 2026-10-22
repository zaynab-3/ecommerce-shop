<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\CheckoutService;
use App\Services\OrderStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ManagementAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        StoreSetting::create(['id' => 1, 'delivery_enabled' => true]);
    }

    private function admin(string $role = 'owner'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function product(array $data = []): Product
    {
        $category = Category::firstOrCreate(['slug' => 'tech'], ['name' => 'Tech']);

        return Product::create([...['category_id' => $category->id, 'name' => 'Useful item', 'slug' => 'product-'.Str::random(8), 'sku' => Str::random(10), 'description' => 'Test item', 'price_cents' => 2500, 'stock_mode' => 'tracked', 'quantity' => 10, 'low_stock_threshold' => 5, 'is_active' => true], ...$data]);
    }

    private function order(Product $product, string $status = 'pending', int $quantity = 2, ?string $date = null): Order
    {
        $order = Order::create(['public_id' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'number' => 'MS-'.Str::random(8), 'customer_name' => 'Test customer', 'phone' => '+96170123456', 'address' => 'Test address', 'delivery_area' => 'Nabatieh', 'subtotal_cents' => $quantity * 2500, 'delivery_cents' => 500, 'total_cents' => $quantity * 2500 + 500, 'status' => $status, 'created_at' => $date ?? now()]);
        $order->items()->create(['product_id' => $product->id, 'name' => 'Original item name', 'sku' => $product->sku, 'tracked_stock' => $product->stock_mode === 'tracked', 'quantity' => $quantity, 'price_cents' => 2500]);

        return $order;
    }

    public function test_analytics_are_owner_only_and_private_with_validated_dates(): void
    {
        $this->get(route('admin.analytics'))->assertRedirect(route('admin.login'));
        foreach (['manager', 'orders'] as $role) {
            $this->actingAs($this->admin($role))->get(route('admin.analytics'))->assertForbidden();
            $this->get(route('admin.analytics.export'))->assertForbidden();
        }
        $this->actingAs($this->admin())->get(route('admin.analytics'))->assertOk()->assertSee('No orders in this period');
        $json = $this->getJson(route('admin.analytics'))->assertOk()->assertJsonStructure(['html', 'updated_at']);
        $this->assertStringContainsString('no-store', $json->headers->get('Cache-Control'));
        $this->getJson(route('admin.analytics', ['period' => 'custom', 'from' => '2026-02-20', 'to' => '2026-02-01']))->assertUnprocessable();
        $this->getJson(route('admin.analytics', ['period' => 'custom']))->assertUnprocessable();
        $this->getJson(route('admin.analytics', ['period' => 'custom', 'from' => now()->addDay()->toDateString(), 'to' => now()->addDay()->toDateString()]))->assertUnprocessable();
        $csv = $this->get(route('admin.analytics.export'))->assertOk()->assertDownload();
        $this->assertStringContainsString('MESH sales report', $csv->streamedContent());
    }

    public function test_sales_counts_snapshots_and_chart_use_the_selected_placement_dates(): void
    {
        $product = $this->product(['quantity' => 3]);
        $delivered = $this->order($product, 'delivered', 2, '2026-01-10 23:59:59');
        $this->order($product, 'pending', 3, '2026-01-10 00:00:00');
        $this->order($product, 'cancelled', 9, '2026-01-10 12:00:00');
        $this->order($product, 'delivered', 10, '2026-01-11 00:00:00');
        $this->order($product, 'delivered', 20, '2026-01-09 23:59:59');
        $delivered->delete(); // A removed delivered order must stay in sales.
        $product->update(['name' => 'Renamed current item', 'price_cents' => 9000]);
        $report = app(AnalyticsService::class)->report(CarbonImmutable::parse('2026-01-10'), CarbonImmutable::parse('2026-01-11'));
        $this->assertSame(3, $report['summary']['orders']);
        $this->assertSame(5000, $report['summary']['sales']);
        $this->assertSame(5500, $report['summary']['collected']);
        $this->assertSame(8000, $report['summary']['outstanding']);
        $this->assertSame(1, $report['summary']['deleted']);
        $this->assertSame(33.3, $report['summary']['cancellation_rate']);
        $this->assertSame(5, (int) $report['units']->ordered);
        $this->assertSame(2, (int) $report['units']->delivered);
        $this->assertCount(1, $report['topProducts']);
        $this->assertSame('Renamed current item', $report['topProducts'][0]->name);
        $this->assertSame(5000, (int) $report['topProducts'][0]->sales);
        $this->assertSame([['label' => '10 Jan', 'orders' => 3, 'sales' => 5000]], $report['chart']);
        $this->assertSame('Nabatieh', $report['areas'][0]->name);
        $this->assertSame(5, (int) $report['categories'][0]->units);
        foreach ([30 => 'day', 90 => 'week', 365 => 'month', 1500 => 'year'] as $days => $bucket) {
            $range = app(AnalyticsService::class)->report(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-01-01')->addDays($days));
            $this->assertSame($bucket, $range['bucket']);
            $this->assertSame(80000, array_sum(array_column($range['chart'], 'sales')));
        }
    }

    public function test_inventory_includes_open_hidden_and_low_stock_products(): void
    {
        $this->product(['quantity' => 0]);
        $this->product(['quantity' => 3, 'is_active' => false]);
        $this->product(['quantity' => 20]);
        $this->product(['stock_mode' => 'open', 'quantity' => null, 'manual_stock_status' => 'low']);
        $this->product(['stock_mode' => 'open', 'quantity' => null, 'manual_stock_status' => 'out']);
        $report = app(AnalyticsService::class)->report(CarbonImmutable::today()->subDay(), CarbonImmutable::today());
        $this->assertSame(5, $report['inventory']['products']);
        $this->assertSame(23, $report['inventory']['units']);
        $this->assertSame(57500, $report['inventory']['retail_value']);
        $this->assertSame(2, $report['inventory']['out']);
        $this->assertSame(2, $report['inventory']['low']);
        $this->assertSame(1, $report['inventory']['hidden']);
        $this->assertCount(4, $report['attention']);
    }

    public function test_deleting_unshipped_orders_cancels_and_restores_stock_once(): void
    {
        $this->actingAs($this->admin());
        foreach (['pending', 'confirmed', 'processing'] as $status) {
            $p = $this->product(['quantity' => 4]);
            $order = $this->order($p, $status, 3);
            $this->delete(route('admin.orders.destroy', $order))->assertRedirect(route('admin.orders.index'));
            $this->assertNull(Order::find($order->id));
            $deleted = Order::withTrashed()->findOrFail($order->id);
            $this->assertSame('cancelled', $deleted->status);
            $this->assertNotNull($deleted->stock_restored_at);
            $this->assertSame(7, $p->fresh()->quantity);
            app(OrderStatusService::class)->delete($deleted);
            $this->assertSame(7, $p->fresh()->quantity);
            $this->delete(route('admin.orders.destroy', $order))->assertNotFound();
            $this->assertDatabaseHas('audit_logs', ['action' => 'order.deleted', 'subject' => $order->number]);
            $this->get(route('admin.orders.index'))->assertDontSee($order->number);
        }
    }

    public function test_deleting_shipped_delivered_cancelled_and_open_stock_preserves_correct_stock(): void
    {
        $this->actingAs($this->admin());
        foreach (['shipped', 'delivered', 'cancelled'] as $status) {
            $p = $this->product(['quantity' => 4]);
            $order = $this->order($p, $status, 3);
            if ($status === 'cancelled') {
                $order->update(['stock_restored_at' => now()]);
            }
            $this->delete(route('admin.orders.destroy', $order))->assertRedirect();
            $this->assertSame($status, Order::withTrashed()->find($order->id)->status);
            $this->assertSame(4, $p->fresh()->quantity);
        }
        $p = $this->product(['stock_mode' => 'open', 'quantity' => null]);
        $this->delete(route('admin.orders.destroy', $this->order($p)))->assertRedirect();
        $this->assertNull($p->fresh()->quantity);
    }

    public function test_order_delete_is_owner_only_and_checkout_replay_cannot_duplicate_deleted_order(): void
    {
        $p = $this->product();
        $zone = DeliveryZone::create(['name' => 'Beirut', 'governorate' => 'Beirut', 'fee_cents' => 500, 'is_active' => true]);
        $data = ['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Test Customer', 'phone' => '+96170123456', 'address' => 'Valid test address', 'delivery_zone_id' => $zone->id];
        $cart = [$p->id.'-none' => ['product_id' => $p->id, 'color_id' => null, 'quantity' => 2]];
        $order = app(CheckoutService::class)->place($data, $cart);
        foreach (['manager', 'orders'] as $role) {
            $this->actingAs($this->admin($role))->delete(route('admin.orders.destroy', $order))->assertForbidden();
        }
        $this->actingAs($this->admin())->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $replayed = app(CheckoutService::class)->place($data, $cart);
        $this->assertSame($order->id, $replayed->id);
        $this->assertTrue($replayed->trashed());
        $this->assertSame(10, $p->fresh()->quantity);
        $this->assertSame(1, Order::withTrashed()->count());
    }

    public function test_anonymous_favorite_totals_count_last_saved_state_and_repeated_additions(): void
    {
        $p = $this->product();
        $this->postJson(route('saved.toggle', $p))->assertOk();
        $visitor = session('favorite_visitor_key');
        $this->postJson(route('saved.toggle', $p))->assertOk();
        $this->assertDatabaseHas('product_favorites', ['product_id' => $p->id, 'visitor_key' => $visitor, 'is_saved' => 0, 'save_count' => 1]);
        $this->postJson(route('saved.toggle', $p))->assertOk();
        $this->withSession(['saved' => [], 'favorite_visitor_key' => Str::random(64)])->postJson(route('saved.toggle', $p))->assertOk();
        $report = app(AnalyticsService::class)->report(CarbonImmutable::parse('2020-01-01'), CarbonImmutable::parse('2020-01-02'));
        $this->assertSame(2, (int) $report['favoriteTotals']->saved);
        $this->assertSame(3, (int) $report['favoriteTotals']->additions);
        $this->assertSame($p->name, $report['favorites'][0]->name);
    }

    public function test_inline_quantity_is_absolute_revision_checked_and_prices_are_exact_cents(): void
    {
        $p = $this->product();
        $this->actingAs($this->admin('manager'));
        $this->patchJson(route('admin.products.quick', $p), ['quantity' => 37, 'stock_revision' => 0])->assertOk()->assertJsonPath('product.quantity', 37);
        $this->patchJson(route('admin.products.quick', $p), ['quantity' => 99, 'stock_revision' => 0])->assertConflict()->assertJsonPath('product.quantity', 37);
        $this->assertSame(37, $p->fresh()->quantity);
        $this->patchJson(route('admin.products.quick', $p), ['price' => '19.99', 'compare_price' => '25.50'])->assertOk()->assertJsonPath('product.price', '19.99');
        $this->assertSame(1999, $p->fresh()->price_cents);
        $this->assertSame(2550, $p->fresh()->compare_price_cents);
        $this->patchJson(route('admin.products.quick', $p), ['price' => '19.99', 'compare_price' => ''])->assertOk();
        $this->assertNull($p->fresh()->compare_price_cents);
        foreach ([['price' => '-1'], ['price' => '1.999'], ['quantity' => 1], ['quantity' => 10000001, 'stock_revision' => 3], ['quantity' => 1, 'delta' => 1, 'stock_revision' => 3]] as $bad) {
            $this->patchJson(route('admin.products.quick', $p), $bad)->assertUnprocessable();
        }
        $this->actingAs($this->admin('orders'))->patchJson(route('admin.products.quick', $p), ['price' => '5'])->assertForbidden();
    }

    public function test_products_and_categories_paginate_fifteen_and_keep_filters(): void
    {
        for ($i = 0; $i < 18; $i++) {
            $this->product(['name' => 'Filtered item '.$i]);
            Category::create(['name' => 'Filtered category '.$i, 'slug' => 'filtered-'.$i]);
        }
        $this->actingAs($this->admin());
        foreach ([['admin.products.index', 'products'], ['admin.categories.index', 'categories']] as [$route, $variable]) {
            $response = $this->get(route($route, ['q' => 'Filtered']));
            $response->assertOk()->assertViewHas($variable, fn ($rows) => $rows->count() === 15 && $rows->total() === 18)->assertSee('q=Filtered');
            $this->get(route($route, ['q' => 'Filtered', 'page' => 2]))->assertOk()->assertViewHas($variable, fn ($rows) => $rows->count() === 3);
        }
    }

    public function test_category_reparenting_rejects_cycles_and_third_levels(): void
    {
        $this->actingAs($this->admin());
        $root = Category::create(['name' => 'Root', 'slug' => 'root']);
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $root->id]);
        $other = Category::create(['name' => 'Other', 'slug' => 'other']);
        foreach ([[$root, $root], [$root, $child], [$root, $other], [$other, $child]] as [$category, $parent]) {
            $this->patchJson(route('admin.categories.update', $category), ['parent_id' => $parent->id])->assertUnprocessable();
        }
        $this->patch(route('admin.categories.update', $child), ['parent_id' => $other->id, 'name' => 'Moved child'])->assertSessionHasNoErrors();
        $this->assertSame($other->id, $child->fresh()->parent_id);
        $this->get(route('admin.categories.index', ['parent' => $other->id]))->assertOk()->assertSee('Moved child')->assertViewHas('categories', fn ($rows) => $rows->count() === 2 && ! $rows->contains('id', $root->id));
    }

    public function test_delivery_area_can_be_added_with_price_edited_inline_and_fees_disabled(): void
    {
        $this->actingAs($this->admin('manager'))->post(route('admin.delivery.store'), ['name' => 'New Nabatieh', 'governorate' => 'Nabatieh', 'fee' => '4.75', 'is_active' => 1])->assertRedirect();
        $zone = DeliveryZone::where('name', 'New Nabatieh')->firstOrFail();
        $this->assertSame(475, $zone->fee_cents);
        $this->patchJson(route('admin.delivery.quick', $zone), ['fee' => '6.25', 'is_active' => 0])->assertOk();
        $this->assertSame(625, $zone->fresh()->fee_cents);
        $this->assertFalse($zone->fresh()->is_active);
        $this->patch(route('admin.delivery.settings'), ['delivery_enabled' => 0])->assertForbidden();
        StoreSetting::current();
        $this->actingAs($this->admin())->patch(route('admin.delivery.settings'), ['delivery_enabled' => 0])->assertRedirect();
        $this->assertFalse(StoreSetting::current()->delivery_enabled);
        $this->get(route('admin.delivery.index'))->assertOk()->assertSee('Checkout delivery pricing')->assertSee('Add delivery area')->assertDontSee('JSON API');
    }

    public function test_team_editor_updates_identity_without_resetting_password_and_preserves_owner_access(): void
    {
        $owner = $this->admin();
        $member = $this->admin('orders');
        $hash = $member->password;
        $this->actingAs($owner)->patch(route('admin.team.update', $member), ['name' => 'Updated member', 'email' => 'updated@example.test', 'role' => 'manager', 'is_active' => 1, 'password' => ''])->assertSessionHasNoErrors();
        $this->assertSame('Updated member', $member->fresh()->name);
        $this->assertSame('updated@example.test', $member->fresh()->email);
        $this->assertSame($hash, $member->fresh()->password);
        $this->patch(route('admin.team.update', $member), ['email' => $owner->email, 'role' => 'manager', 'is_active' => 1])->assertSessionHasErrors('email');
        $this->patch(route('admin.team.update', $owner), ['role' => 'orders', 'is_active' => 1])->assertUnprocessable();
        $this->get(route('admin.team.index'))->assertOk()->assertSee('data-team-edit', false)->assertDontSee($hash);
    }

    public function test_checkout_phone_is_optional_only_when_a_whatsapp_handoff_is_available(): void
    {
        $p = $this->product();
        StoreSetting::first()->update(['delivery_enabled' => false, 'whatsapp_enabled' => true, 'whatsapp_recipient' => '96170123456']);
        Cache::forget('store.settings');
        $key = (string) Str::uuid();
        $cart = [$p->id.'-none' => ['product_id' => $p->id, 'color_id' => null, 'quantity' => 1]];
        $data = ['idempotency_key' => $key, 'customer_name' => 'WhatsApp customer', 'address' => 'Valid street building 12'];
        $this->withSession(['cart' => $cart, 'checkout_key' => $key])->get(route('checkout'))->assertOk()->assertSee('(optional)');
        $response = $this->postJson(route('checkout.store'), $data)->assertOk();
        $order = Order::firstOrFail();
        $this->assertNull($order->phone);
        $this->assertStringStartsWith('https://wa.me/96170123456?', $response->json('whatsapp_url'));
        $this->assertStringNotContainsString('Phone:', urldecode($response->json('whatsapp_url')));
        $this->actingAs($this->admin())->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('href="tel:"', false)->assertSee('Phone not provided');

        foreach ([[false, '96170123456'], [true, 'invalid']] as [$enabled, $recipient]) {
            StoreSetting::first()->update(['whatsapp_enabled' => $enabled, 'whatsapp_recipient' => $recipient]);
            Cache::forget('store.settings');
            $key = (string) Str::uuid();
            $this->withSession(['cart' => $cart, 'checkout_key' => $key])->postJson(route('checkout.store'), [...$data, 'idempotency_key' => $key])->assertUnprocessable()->assertJsonValidationErrors('phone');
            $this->assertSame(1, Order::count());
        }
    }

    public function test_optional_phone_still_validates_supplied_values_and_normalizes_them(): void
    {
        StoreSetting::first()->update(['delivery_enabled' => false, 'whatsapp_enabled' => true, 'whatsapp_recipient' => '96170123456']);
        Cache::forget('store.settings');
        $p = $this->product();
        $key = (string) Str::uuid();
        $cart = [$p->id.'-none' => ['product_id' => $p->id, 'color_id' => null, 'quantity' => 1]];
        $data = ['idempotency_key' => $key, 'customer_name' => 'WhatsApp customer', 'address' => 'Valid street building 12'];
        $this->withSession(['cart' => $cart, 'checkout_key' => $key]);
        foreach (['abc', '+4470123456', ['unexpected']] as $phone) {
            $this->postJson(route('checkout.store'), [...$data, 'phone' => $phone])->assertUnprocessable()->assertJsonValidationErrors('phone');
        }
        $this->postJson(route('checkout.store'), [...$data, 'phone' => '03 123 456'])->assertOk();
        $this->assertSame('+9613123456', Order::firstOrFail()->phone);
    }

    public function test_deleted_order_confirmation_stays_private_and_cannot_send_cancelled_orders_to_whatsapp(): void
    {
        StoreSetting::first()->update(['whatsapp_enabled' => true, 'whatsapp_recipient' => '96170123456']);
        Cache::forget('store.settings');
        $order = $this->order($this->product());
        $this->actingAs($this->admin())->delete(route('admin.orders.destroy', $order))->assertRedirect();
        $this->get(route('orders.confirmation', $order->public_id))->assertNotFound();
        $this->withSession(['order_access' => [$order->public_id], 'whatsapp_order' => $order->public_id])->get(route('orders.confirmation', $order->public_id))->assertOk()->assertSee('Order cancelled')->assertDontSee('data-whatsapp-order', false);
    }

    public function test_favorites_cap_marks_an_evicted_browser_save_inactive(): void
    {
        $ids = [];
        for ($i = 0; $i < 100; $i++) {
            $ids[] = $this->product()->id;
        }
        $visitor = Str::random(64);
        DB::table('product_favorites')->insert(['visitor_key' => $visitor, 'product_id' => $ids[0], 'is_saved' => true, 'save_count' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $new = $this->product();
        $this->withSession(['saved' => $ids, 'favorite_visitor_key' => $visitor])->postJson(route('saved.toggle', $new))->assertOk()->assertJsonPath('count', 100);
        $this->assertNotContains($ids[0], session('saved'));
        $this->assertContains($new->id, session('saved'));
        $this->assertDatabaseHas('product_favorites', ['visitor_key' => $visitor, 'product_id' => $ids[0], 'is_saved' => false, 'save_count' => 1]);
        $this->assertDatabaseHas('product_favorites', ['visitor_key' => $visitor, 'product_id' => $new->id, 'is_saved' => true, 'save_count' => 1]);
    }
}
