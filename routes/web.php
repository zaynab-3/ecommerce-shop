<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogRevisionController;
use App\Http\Controllers\CategoryIconController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CookieNoticeController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StoreThemeController;
use App\Http\Middleware\PublishCatalogRevision;
use Illuminate\Support\Facades\Route;

// Used when the web server cannot serve public/storage (for example, exFAT).
Route::get('/storage/products/{filename}', ProductImageController::class)
    ->withoutMiddleware('web')
    ->where('filename', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}(-480)?\.webp')
    ->name('product-images.show');

Route::get('/category-icons/{filename}', CategoryIconController::class)->withoutMiddleware('web')->where('filename', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\.svg')->name('category-icons.show');

Route::get('/', [StorefrontController::class, 'index'])->name('home');
Route::get('/theme/{color}.css', StoreThemeController::class)->where('color', '[0-9a-fA-F]{6}')->withoutMiddleware('web')->name('store.theme');
Route::get('/shop', [StorefrontController::class, 'index'])->name('shop');
Route::get('/catalog/version', CatalogRevisionController::class)->withoutMiddleware('web')->middleware('throttle:catalog-sync')->name('catalog.version');
Route::get('/search/suggestions', SearchSuggestionController::class)->withoutMiddleware('web')->middleware('throttle:search')->name('search.suggestions');
Route::get('/products/{product:slug}', [StorefrontController::class, 'show'])->name('products.show');
Route::get('/stock', [StorefrontController::class, 'stock'])->withoutMiddleware('web')->middleware('throttle:stock')->name('stock');
Route::get('/delivery-areas', [StorefrontController::class, 'areas'])->withoutMiddleware('web')->middleware('throttle:stock')->name('delivery.areas');
Route::post('/saved/{product}', [StorefrontController::class, 'save'])->middleware('throttle:cart')->block(10, 10)->name('saved.toggle');
Route::get('/cart', [CartController::class, 'index'])->block(10, 10)->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->middleware(['throttle:cart', 'Illuminate\Session\Middleware\AuthenticateSession'])->block(10, 10)->name('cart.add');
Route::patch('/cart/{key}', [CartController::class, 'update'])->middleware('throttle:cart')->block(10, 10)->name('cart.update');
Route::get('/checkout', [CheckoutController::class, 'index'])->block(10, 10)->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->block(15, 15)->name('checkout.store');
Route::get('/orders/{order:public_id}', [CheckoutController::class, 'confirmation'])->name('orders.confirmation');
Route::view('/help', 'store.help')->name('help');
Route::view('/privacy-policy', 'store.privacy')->name('privacy');
Route::view('/terms-and-conditions', 'store.terms')->name('terms');
Route::post('/cookie-notice', CookieNoticeController::class)->block(10, 10)->name('cookies.acknowledge');
Route::prefix(config('shop.admin_path'))->name('admin.')->group(function () {
    Route::get('/login', [Admin\AuthController::class, 'create'])->name('login');
    Route::post('/login', [Admin\AuthController::class, 'store'])->middleware('throttle:admin-login')->name('login.store');
    Route::middleware('admin')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');
        Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
        Route::middleware(['admin:catalog', PublishCatalogRevision::class])->group(function () {
            Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
            Route::patch('/products/{product}/quick', [Admin\ProductController::class, 'quick'])->name('products.quick');
            Route::patch('/products/{product}/images/{image}', [Admin\ProductController::class, 'image'])->name('products.image');
            Route::delete('/products/{product}/images/{image}', [Admin\ProductController::class, 'deleteImage'])->name('products.image.delete');
            Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
            Route::patch('/categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('/sort/{type}/{id}', Admin\SortController::class)->name('sort');
            Route::get('/delivery', [Admin\DeliveryController::class, 'index'])->name('delivery.index');
            Route::patch('/delivery', [Admin\DeliveryController::class, 'update'])->name('delivery.update');
            Route::post('/delivery', [Admin\DeliveryController::class, 'store'])->name('delivery.store');
        });
        Route::middleware('admin:owner')->group(function () {
            Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings.index');
            Route::patch('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
            Route::get('/team', [Admin\TeamController::class, 'index'])->name('team.index');
            Route::post('/team', [Admin\TeamController::class, 'store'])->name('team.store');
            Route::patch('/team/{user}', [Admin\TeamController::class, 'update'])->name('team.update');
        });
    });
});
