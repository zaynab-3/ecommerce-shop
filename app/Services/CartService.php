<?php

namespace App\Services;

use App\Models\Product;
use App\Models\DeliveryZone;
use App\Models\StoreSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CartService
{
    public function viewData(): array
    {
        $lines = $this->lines();
        $settings = StoreSetting::current();
        $zones = $settings->delivery_enabled ? DeliveryZone::where('is_active', true)->orderBy('governorate')->orderBy('name')->get() : collect();
        $selectedZone = $zones->firstWhere('id', (int) session('delivery_zone_id'));
        if ($lines->isNotEmpty() && (! session('checkout_key') || session('checkout_key') === session('checkout_completed_key'))) {
            session(['checkout_key' => (string) Str::uuid()]);
        }

        return ['lines' => $lines, 'subtotal' => $lines->sum('total'), 'settings' => $settings, 'zones' => $zones, 'selectedZone' => $selectedZone];
    }

    public function lines(): Collection
    {
        $cart = session('cart', []);
        $products = Product::with(['images', 'colors', 'category.parent'])->whereIn('id', array_unique(array_column($cart, 'product_id')))->get()->keyBy('id');

        return collect($cart)->map(function ($line, $key) use ($products) {
            $p = $products->get($line['product_id']);
            if (! $p) {
                return null;
            }$color = $p->colors->firstWhere('id', $line['color_id']);

            return ['key' => $key, 'product' => $p, 'color' => $color, 'quantity' => $line['quantity'], 'total' => $p->price_cents * $line['quantity'], 'available' => $p->is_active && $p->category->is_active && (! $p->category->parent || $p->category->parent->is_active) && $p->stock_status !== 'out'];
        })->filter();
    }

    public function subtotal(): int
    {
        return $this->lines()->sum('total');
    }
}
