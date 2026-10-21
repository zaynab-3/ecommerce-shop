<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Services\CatalogRevision;
use App\Services\WhatsAppOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StorefrontController extends Controller
{
    public function index(Request $r)
    {
        $catalogRevision = CatalogRevision::current();
        $filters = $r->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|string|max:100', 'sort' => 'nullable|in:featured,newest,price_asc,price_desc', 'available' => 'nullable|boolean', 'saved' => 'nullable|boolean']);
        $categories = Cache::remember('catalog.categories.'.$catalogRevision, 60, fn () => Category::visible()->whereNull('parent_id')->with(['children' => fn ($q) => $q->visible()])->withCount(['products' => fn ($q) => $q->visible()])->orderBy('position')->get());
        $query = Product::visible()->with(['images', 'colors', 'category']);
        if ($r->filled('q')) {
            $term = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']);
            $query->whereRaw("name LIKE ? ESCAPE '!'", ['%'.$term.'%']);
        }
        $selected = null;
        if ($r->filled('category')) {
            $selected = Category::visible()->where('slug', $filters['category'])->firstOrFail();
            $query->whereHas('category', fn ($q) => $q->where('id', $selected->id)->orWhere('parent_id', $selected->id));
        }
        if ($r->boolean('available')) {
            $query->where(fn ($q) => $q->where(fn ($q) => $q->where('stock_mode', 'tracked')->where('quantity', '>', 0))->orWhere(fn ($q) => $q->where('stock_mode', 'open')->where('manual_stock_status', '!=', 'out')));
        }
        if ($r->boolean('saved')) {
            $query->whereIn('id', session('saved', []));
        }
        match ($r->input('sort', 'featured')) {
            'newest' => $query->orderByDesc('id'),'price_asc' => $query->orderBy('price_cents'),'price_desc' => $query->orderByDesc('price_cents'),default => $query->orderByDesc('is_featured')->orderBy('position')
        };
        $products = $query->orderBy('id')->paginate(12)->withQueryString();

        return view('store.index', compact('categories', 'products', 'selected', 'catalogRevision'));
    }

    public function show(Product $product, WhatsAppOrderService $whatsapp)
    {
        $catalogRevision = CatalogRevision::current();
        abort_unless(Product::visible()->whereKey($product->id)->exists(), 404);
        $product->load(['images', 'colors', 'category.parent']);
        $related = Product::visible()->with(['images', 'colors', 'category'])->where('category_id', $product->category_id)->whereKeyNot($product->id)->limit(4)->get();

        $whatsappUrl = $whatsapp->productUrl($product);

        return view('store.product', compact('product', 'related', 'whatsappUrl', 'catalogRevision'));
    }

    public function stock(Request $r)
    {
        $data = $r->validate(['ids' => 'required|array|max:48', 'ids.*' => 'required|integer|distinct|min:1']);
        $ids = $data['ids'];
        sort($ids);
        $rows = Cache::remember('stock.'.CatalogRevision::current().'.'.hash('sha256', json_encode($ids)), 5, fn () => Product::visible()->whereIn('id', $ids)->get(['id', 'stock_mode', 'quantity', 'manual_stock_status', 'low_stock_threshold'])->map(fn ($p) => ['id' => $p->id, 'status' => $p->stock_status, 'quantity' => $p->stock_mode === 'tracked' ? $p->quantity : null]));

        return response()->json($rows)->header('Cache-Control', 'no-store');
    }

    public function save(Request $r, Product $product)
    {
        $saved = session('saved', []);
        $saved = in_array($product->id, $saved) ? array_values(array_diff($saved, [$product->id])) : array_slice([...$saved, $product->id], -100);
        session(['saved' => $saved]);

        if ($r->expectsJson()) {
            return response()->json(['product_id' => $product->id, 'saved' => in_array($product->id, $saved), 'count' => count($saved), 'message' => in_array($product->id, $saved) ? 'Added to your saved items.' : 'Removed from saved items.']);
        }

        return back()->with('success', in_array($product->id, $saved) ? 'Added to your saved items.' : 'Removed from saved items.');
    }

    public function areas()
    {
        return response()->json(DeliveryZone::where('is_active', true)->orderBy('governorate')->orderBy('name')->get(['id', 'name', 'governorate', 'fee_cents']));
    }
}
