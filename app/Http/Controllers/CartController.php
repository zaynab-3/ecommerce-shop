<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $r, CartService $cart)
    {
        $data = $r->validate(['delivery_zone_id' => 'nullable|integer']);
        if ($r->has('delivery_zone_id') && ! $r->filled('delivery_zone_id')) {
            session()->forget('delivery_zone_id');
        } elseif ($r->filled('delivery_zone_id')) {
            $zone = DeliveryZone::whereKey($data['delivery_zone_id'])->where('is_active', true)->firstOrFail();
            session(['delivery_zone_id' => $zone->id]);
        }
        if ($r->expectsJson()) {
            return $this->snapshot($cart);
        }

        return view('store.cart', $cart->viewData());
    }

    private function snapshot(CartService $cart, ?string $message = null)
    {
        $data = $cart->viewData();

        return response()->json([
            'html' => view('store.partials.cart-content', $data)->render(),
            'count' => collect(session('cart', []))->sum('quantity'),
            'subtotal_cents' => $data['subtotal'],
            'delivery_cents' => $data['selectedZone']?->fee_cents ?? 0,
            'message' => $message,
        ]);
    }

    public function add(Request $r, Product $product)
    {
        $data = $r->validate(['quantity' => 'required|integer|min:1|max:99', 'color_id' => 'nullable|integer', 'buy_now' => 'sometimes|boolean']);
        abort_unless(Product::visible()->whereKey($product->id)->exists(), 404);
        $color = $data['color_id'] ?? null;
        if ($color && ! $product->colors()->whereKey($color)->exists()) {
            throw ValidationException::withMessages(['color_id' => 'Choose a valid color.']);
        }
        if ($product->colors()->exists() && ! $color) {
            throw ValidationException::withMessages(['color_id' => 'Please select a color.']);
        }
        $cart = session('cart', []);
        $key = $product->id.'-'.($color ?? 'none');
        if (! isset($cart[$key]) && count($cart) >= 50) {
            throw ValidationException::withMessages(['cart' => 'Your cart can hold up to 50 different items.']);
        }
        $quantity = ($cart[$key]['quantity'] ?? 0) + $data['quantity'];
        $allQty = collect($cart)->where('product_id', $product->id)->sum('quantity') + $data['quantity'];
        if ($quantity > 99 || $product->stock_status === 'out' || ($product->stock_mode === 'tracked' && $allQty > $product->quantity)) {
            throw ValidationException::withMessages(['quantity' => 'The requested quantity is not available.']);
        }
        $cart[$key] = ['product_id' => $product->id, 'color_id' => $color, 'quantity' => $quantity];
        session(['cart' => $cart]);

        if ($r->expectsJson()) {
            return response()->json(['message' => 'Added to your cart.', 'count' => collect($cart)->sum('quantity'), 'redirect' => $r->boolean('buy_now') ? route('checkout') : null]);
        }

        return ($r->boolean('buy_now') ? redirect()->route('checkout') : back())->with('success', 'Added to your cart.');
    }

    public function update(Request $r, string $key, CartService $cartService)
    {
        $data = $r->validate(['quantity' => 'required|integer|min:0|max:99']);
        $data['quantity'] = (int) $data['quantity'];
        $cart = session('cart', []);
        abort_unless(isset($cart[$key]), 404);
        if ($data['quantity'] === 0) {
            unset($cart[$key]);
        } else {
            $line = $cart[$key];
            $p = Product::visible()->findOrFail($line['product_id']);
            $all = collect($cart)->where('product_id', $p->id)->sum('quantity') - $line['quantity'] + $data['quantity'];
            if ($p->stock_status === 'out' || ($p->stock_mode === 'tracked' && $all > $p->quantity)) {
                throw ValidationException::withMessages(['quantity' => 'That quantity is no longer available.']);
            }$cart[$key]['quantity'] = $data['quantity'];
        }session(['cart' => $cart]);

        if ($r->expectsJson()) {
            return $this->snapshot($cartService, 'Your cart has been updated.');
        }

        return back()->with('success', 'Your cart has been updated.');
    }
}
