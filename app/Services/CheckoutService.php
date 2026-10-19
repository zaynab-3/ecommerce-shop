<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function place(array $data, array $cart): Order
    {
        return DB::transaction(function () use ($data, $cart) {
            if ($existing = Order::where('idempotency_key', $data['idempotency_key'])->first()) {
                return $existing;
            }
            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your bag is empty.']);
            }
            foreach ($cart as $line) {
                if (! is_numeric($line['quantity']) || $line['quantity'] < 1 || $line['quantity'] > 99 || (int) $line['quantity'] != $line['quantity']) {
                    throw ValidationException::withMessages(['cart' => 'Please update the quantities in your bag before checking out.']);
                }
            }
            $setting = StoreSetting::firstOrCreate(['id' => 1]);
            $zone = null;
            $fee = 0;
            if ($setting->delivery_enabled) {
                $zone = DeliveryZone::whereKey($data['delivery_zone_id'] ?? 0)->where('is_active', true)->first();
                if (! $zone) {
                    throw ValidationException::withMessages(['delivery_zone_id' => 'Please choose an available delivery area.']);
                }$fee = $zone->fee_cents;
            }
            $products = Product::with(['category.parent', 'colors'])->whereIn('id', array_column($cart, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $wanted = collect($cart)->groupBy('product_id')->map(fn ($rows) => $rows->sum('quantity'));
            foreach ($wanted as $id => $qty) {
                $p = $products->get($id);
                if (! $p || ! $p->is_active || ! $p->category->is_active || ($p->category->parent && ! $p->category->parent->is_active) || $p->stock_status === 'out' || ($p->stock_mode === 'tracked' && $p->quantity < $qty)) {
                    throw ValidationException::withMessages(['cart' => ($p?->name ?? 'An item').' no longer has the requested stock. Please update your bag.']);
                }
            }
            $rows = [];
            $subtotal = 0;
            foreach ($cart as $line) {
                $p = $products[$line['product_id']];
                $color = $line['color_id'] ? $p->colors->firstWhere('id', $line['color_id']) : null;
                if ($p->colors->isNotEmpty() && ! $color) {
                    throw ValidationException::withMessages(['cart' => 'Please select a currently available color for '.$p->name.'.']);
                }
                if ($line['color_id'] && ! $color) {
                    throw ValidationException::withMessages(['cart' => 'A selected color is no longer available.']);
                }$subtotal += $p->price_cents * $line['quantity'];
                $rows[] = ['product_id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'color' => $color?->name, 'tracked_stock' => $p->stock_mode === 'tracked', 'quantity' => $line['quantity'], 'price_cents' => $p->price_cents];
            }
            foreach ($wanted as $id => $qty) {
                $p = $products[$id];
                if ($p->stock_mode === 'tracked') {
                    $updated = Product::whereKey($id)->where('quantity', '>=', $qty)->decrement('quantity', $qty, ['stock_revision' => DB::raw('stock_revision + 1')]);
                    if (! $updated) {
                        throw ValidationException::withMessages(['cart' => 'Stock just changed. Please try again.']);
                    }
                }
            }
            $order = Order::create(['public_id' => (string) Str::uuid(), 'idempotency_key' => $data['idempotency_key'], 'number' => 'MS-'.strtoupper(Str::random(8)), 'customer_name' => $data['customer_name'], 'phone' => $data['phone'], 'email' => $data['email'] ?? null, 'address' => $data['address'], 'notes' => $data['notes'] ?? null, 'delivery_zone_id' => $zone?->id, 'delivery_area' => $zone?->name, 'subtotal_cents' => $subtotal, 'delivery_cents' => $fee, 'total_cents' => $subtotal + $fee]);
            $order->items()->createMany($rows);

            return $order;
        }, 5);
    }
}
