<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
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
