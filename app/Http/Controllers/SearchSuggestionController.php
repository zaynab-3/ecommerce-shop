<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:100']);
        $term = trim($data['q'] ?? '');
        if (mb_strlen($term) < 2) {
            return response()->json(['products' => []])->header('Cache-Control', 'no-store');
        }
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
        $ids = Cache::remember('search.suggestions.'.CatalogRevision::current().'.'.hash('sha256', mb_strtolower($term)), 10, fn () => Product::visible()
            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])->orderBy('name')->orderBy('id')->limit(6)->pluck('id')->all());
        // Recheck visibility and prices on every response; only the candidate IDs
        // are cached. The three bounded eager-load queries avoid N+1 reads.
        $products = Product::visible()->with(['images', 'category'])->whereIn('id', $ids)->orderBy('name')->orderBy('id')->get();

        return response()->json(['products' => $products->map(fn ($product) => [
            'name' => $product->name,
            'url' => route('products.show', $product),
            'image' => $product->images->first()?->thumbnail_url ?? $product->image_url,
            'category' => $product->category->name,
            'price_cents' => $product->price_cents,
            'stock' => $product->stock_status,
        ])])->header('Cache-Control', 'no-store');
    }
}
