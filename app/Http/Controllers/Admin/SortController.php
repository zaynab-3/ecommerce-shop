<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SortController extends Controller
{
    public function __invoke(Request $r, string $type, int $id)
    {
        $data = $r->validate(['direction' => 'required|in:up,down']);
        $model = match ($type) {
            'products' => Product::class,'categories' => Category::class,default => abort(404)
        };
        DB::transaction(function () use ($data, $model, $id) {
            $item = $model::whereKey($id)->lockForUpdate()->firstOrFail();
            $query = $model::query();
            if ($model === Category::class) {
                $query->where('parent_id', $item->parent_id);
            }$rows = $query->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            $index = $rows->search(fn ($x) => $x->id === $id);
            $other = $data['direction'] === 'up' ? $index - 1 : $index + 1;
            if ($other < 0 || $other >= $rows->count()) {
                return;
            }$all = $rows->all();
            [$all[$index],$all[$other]] = [$all[$other], $all[$index]];
            foreach ($all as $i => $row) {
                $row->update(['position' => $i]);
            }
        });
        Cache::forget('catalog.categories');

        return back()->with('success', 'Order updated.');
    }
}
