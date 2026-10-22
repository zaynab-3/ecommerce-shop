<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function report(CarbonImmutable $from, CarbonImmutable $until): array
    {
        // Removed orders stay in the ledger; only delivered orders count as sales.
        $orders = Order::withTrashed()->where('created_at', '>=', $from)->where('created_at', '<', $until);
        $summary = (clone $orders)->selectRaw("COUNT(*) AS orders, SUM(CASE WHEN status = 'delivered' THEN subtotal_cents ELSE 0 END) AS sales, SUM(CASE WHEN status = 'delivered' THEN total_cents ELSE 0 END) AS collected, SUM(CASE WHEN status IN ('pending','confirmed','processing','shipped') THEN total_cents ELSE 0 END) AS outstanding, SUM(CASE WHEN status = 'cancelled' THEN total_cents ELSE 0 END) AS cancelled_value, SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted")->first();
        $summary = collect($summary->getAttributes())->map(fn ($value) => (int) $value)->all();
        $statusRows = (clone $orders)->selectRaw('status, COUNT(*) AS count, SUM(total_cents) AS value')->groupBy('status')->get()->keyBy('status');
        $statuses = collect(self::STATUSES)->mapWithKeys(fn ($status) => [$status => ['count' => (int) ($statusRows[$status]->count ?? 0), 'value' => (int) ($statusRows[$status]->value ?? 0)]]);
        $summary['completed'] = $statuses['delivered']['count'];
        $summary['open'] = $summary['orders'] - $summary['completed'] - $statuses['cancelled']['count'];
        $summary['average'] = $summary['completed'] ? (int) round($summary['collected'] / $summary['completed']) : 0;
        $summary['cancellation_rate'] = $summary['orders'] ? round($statuses['cancelled']['count'] * 100 / $summary['orders'], 1) : 0;

        $items = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $from)->where('orders.created_at', '<', $until)->where('orders.status', '!=', 'cancelled');
        $units = (clone $items)->selectRaw("COALESCE(SUM(order_items.quantity), 0) AS ordered, COALESCE(SUM(CASE WHEN orders.status = 'delivered' THEN order_items.quantity ELSE 0 END), 0) AS delivered")->first();
        $topProducts = (clone $items)->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw("MAX(products.slug) AS slug, COALESCE(MAX(products.name), MAX(order_items.name)) AS name, MAX(order_items.sku) AS sku, COUNT(DISTINCT orders.id) AS orders, SUM(order_items.quantity) AS units, SUM(CASE WHEN orders.status = 'delivered' THEN order_items.quantity * order_items.price_cents ELSE 0 END) AS sales")
            ->groupByRaw("COALESCE(CAST(order_items.product_id AS CHAR), CONCAT('sku:', order_items.sku))")
            ->orderByDesc('units')->orderBy('name')->limit(8)->get();
        $areas = (clone $orders)->where('status', '!=', 'cancelled')->selectRaw("COALESCE(delivery_area, 'Not specified') AS name, COUNT(*) AS orders, SUM(CASE WHEN status = 'delivered' THEN subtotal_cents ELSE 0 END) AS sales")
            ->groupBy('delivery_area')->orderByDesc('orders')->orderBy('name')->limit(6)->get();
        $categories = (clone $items)->leftJoin('products', 'products.id', '=', 'order_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('categories AS parents', 'parents.id', '=', 'categories.parent_id')
            ->selectRaw("COALESCE(parents.name, categories.name, 'Removed products') AS name, SUM(order_items.quantity) AS units, SUM(CASE WHEN orders.status = 'delivered' THEN order_items.quantity * order_items.price_cents ELSE 0 END) AS sales")
            ->groupByRaw("COALESCE(parents.name, categories.name, 'Removed products')")->orderByDesc('units')->orderBy('name')->limit(6)->get();

        $inventory = Product::selectRaw("COUNT(*) AS products, COALESCE(SUM(CASE WHEN stock_mode = 'tracked' THEN quantity ELSE 0 END), 0) AS units, COALESCE(SUM(CASE WHEN stock_mode = 'tracked' THEN quantity * price_cents ELSE 0 END), 0) AS retail_value, SUM(CASE WHEN stock_mode = 'open' THEN 1 ELSE 0 END) AS open_products, SUM(CASE WHEN (stock_mode = 'tracked' AND quantity = 0) OR (stock_mode = 'open' AND manual_stock_status = 'out') THEN 1 ELSE 0 END) AS `out`, SUM(CASE WHEN (stock_mode = 'tracked' AND quantity > 0 AND quantity <= low_stock_threshold) OR (stock_mode = 'open' AND manual_stock_status = 'low') THEN 1 ELSE 0 END) AS low")->first();
        $inventory = collect($inventory->getAttributes())->map(fn ($value) => (int) $value)->all();
        $inventory['visible'] = Product::visible()->count();
        $inventory['hidden'] = $inventory['products'] - $inventory['visible'];
        $inventory['categories'] = Category::whereNull('parent_id')->count();
        $inventory['subcategories'] = Category::whereNotNull('parent_id')->count();
        $attention = Product::with('category.parent')->where(fn ($query) => $query->where(fn ($q) => $q->where('stock_mode', 'tracked')->whereColumn('quantity', '<=', 'low_stock_threshold'))
            ->orWhere(fn ($q) => $q->where('stock_mode', 'open')->whereIn('manual_stock_status', ['low', 'out'])))
            ->orderByRaw("CASE WHEN (stock_mode = 'tracked' AND quantity = 0) OR manual_stock_status = 'out' THEN 0 ELSE 1 END")
            ->orderBy('quantity')->orderBy('name')->limit(20)->get();

        $favoriteTotals = DB::table('product_favorites')->selectRaw('COALESCE(SUM(is_saved), 0) AS saved, COALESCE(SUM(save_count), 0) AS additions')->first();
        $favorites = DB::table('product_favorites')->join('products', 'products.id', '=', 'product_favorites.product_id')
            ->selectRaw('products.id, products.name, products.slug, products.sku, SUM(is_saved) AS saved, SUM(save_count) AS additions')
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.sku')->orderByDesc('saved')->orderByDesc('additions')->orderBy('products.name')->limit(8)->get();
        $daily = (clone $orders)->selectRaw("DATE(created_at) AS day, COUNT(*) AS orders, SUM(CASE WHEN status = 'delivered' THEN subtotal_cents ELSE 0 END) AS sales")->groupByRaw('DATE(created_at)')->orderBy('day')->get();
        $days = (int) $from->diffInDays($until);
        $bucket = $days <= 31 ? 'day' : ($days <= 120 ? 'week' : ($days <= 1096 ? 'month' : 'year'));
        $bucketStart = fn (CarbonImmutable $date) => match ($bucket) {
            'week' => $date->startOfWeek(), 'month' => $date->startOfMonth(), 'year' => $date->startOfYear(), default => $date->startOfDay()
        };
        $grouped = $daily->groupBy(fn ($row) => $bucketStart(CarbonImmutable::parse($row->day, $from->timezone))->toDateString());
        $chart = [];
        for ($cursor = $bucketStart($from); $cursor->lt($until); $cursor = $cursor->add(1, $bucket)) {
            $rows = $grouped->get($cursor->toDateString(), collect());
            $chart[] = ['label' => $cursor->format($bucket === 'year' ? 'Y' : ($bucket === 'month' ? 'M Y' : 'j M')), 'orders' => (int) $rows->sum('orders'), 'sales' => (int) $rows->sum('sales')];
        }

        return compact('summary', 'statuses', 'units', 'topProducts', 'areas', 'categories', 'inventory', 'attention', 'favoriteTotals', 'favorites', 'chart', 'bucket');
    }
}
