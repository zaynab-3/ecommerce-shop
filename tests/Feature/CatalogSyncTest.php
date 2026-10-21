<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        StoreSetting::create(['id' => 1]);
    }

    private function version(): string
    {
        return $this->getJson(route('catalog.version'))->assertOk()->json('version');
    }

    public function test_version_reads_are_stable_session_free_and_reveal_no_catalog_data(): void
    {
        Category::create(['name' => 'Private draft category', 'slug' => 'private', 'is_active' => false]);
        $first = $this->version();
        $response = $this->getJson(route('catalog.version'))->assertExactJson(['version' => $first]);
        $this->assertSame([], $response->headers->getCookies());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertDontSee('Private draft');
        $this->get(route('shop'))->assertOk();
        $this->assertSame($first, $this->version());
    }

    public function test_category_and_subcategory_edits_invalidate_warmed_navigation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner', 'is_active' => true]));
        $this->get(route('shop'))->assertOk();
        $version = $this->version();
        $this->post(route('admin.categories.store'), ['name' => 'Live category', 'icon' => 'home'])->assertRedirect();
        $parent = Category::where('name', 'Live category')->firstOrFail();
        $this->assertNotSame($version, $version = $this->version());
        $this->get(route('shop'))->assertSee('Live category');
        $this->post(route('admin.categories.store'), ['name' => 'Live child', 'parent_id' => $parent->id])->assertRedirect();
        $child = Category::where('name', 'Live child')->firstOrFail();
        $this->assertNotSame($version, $version = $this->version());
        $this->get(route('shop'))->assertSee('Live child');
        $this->patch(route('admin.categories.update', $child), ['name' => 'Renamed child'])->assertRedirect();
        $this->assertNotSame($version, $version = $this->version());
        $this->get(route('shop'))->assertSee('Renamed child')->assertDontSee('Live child');
        $this->delete(route('admin.categories.destroy', $child))->assertRedirect();
        $this->assertNotSame($version, $version = $this->version());
        $this->get(route('shop'))->assertDontSee('Renamed child');
        $this->delete(route('admin.categories.destroy', $parent))->assertRedirect();
        $this->assertNotSame($version, $this->version());
        $this->get(route('shop'))->assertDontSee('Live category');
    }

    public function test_product_and_image_changes_refresh_catalog_search_and_stock(): void
    {
        $category = Category::create(['name' => 'Live category', 'slug' => 'live']);
        $this->actingAs(User::factory()->create(['role' => 'owner', 'is_active' => true]));
        $this->getJson(route('search.suggestions', ['q' => 'Live widget']))->assertJsonCount(0, 'products');
        $version = $this->version();
        $data = ['name' => 'Live widget', 'sku' => 'LIVE-001', 'category_id' => $category->id, 'description' => 'A live catalog product.', 'price' => '25.00', 'stock_mode' => 'tracked', 'quantity' => 10, 'manual_stock_status' => 'available', 'is_active' => 1];
        $this->post(route('admin.products.store'), $data)->assertRedirect();
        $product = Product::where('sku', 'LIVE-001')->firstOrFail();
        $this->assertNotSame($version, $version = $this->version());
        $this->getJson(route('search.suggestions', ['q' => 'Live widget']))->assertJsonPath('products.0.name', 'Live widget');
        $this->get(route('shop'))->assertSee('Live widget');
        $data['price'] = '32.00';
        $data['stock_revision'] = $product->fresh()->stock_revision;
        $this->put(route('admin.products.update', $product), $data)->assertRedirect();
        $this->assertNotSame($version, $version = $this->version());
        $this->get(route('shop'))->assertSee('$32.00');
        $image = $product->images()->create(['path' => 'images/placeholder.svg', 'alt' => 'Live widget', 'position' => 0]);
        $this->patch(route('admin.products.image', [$product, $image]), ['position' => 1])->assertRedirect();
        $this->assertNotSame($version, $version = $this->version());
        $this->delete(route('admin.products.image.delete', [$product, $image]))->assertRedirect();
        $this->assertNotSame($version, $version = $this->version());
        $this->getJson(route('stock', ['ids' => [$product->id]]))->assertJsonCount(1);
        $this->patch(route('admin.products.quick', $product), ['is_active' => 0])->assertRedirect();
        $this->assertNotSame($version, $this->version());
        $this->get(route('shop'))->assertDontSee('Live widget');
        $this->getJson(route('stock', ['ids' => [$product->id]]))->assertExactJson([]);
        $this->getJson(route('search.suggestions', ['q' => 'Live widget']))->assertJsonCount(0, 'products');
    }

    public function test_denied_or_invalid_writes_do_not_publish_a_revision(): void
    {
        $version = $this->version();
        $this->post(route('admin.categories.store'), ['name' => 'Unauthorized'])->assertRedirect(route('admin.login'));
        $this->assertSame($version, $this->version());
        $this->actingAs(User::factory()->create(['role' => 'owner', 'is_active' => true]));
        $this->post(route('admin.categories.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->assertSame($version, $this->version());
    }
}
