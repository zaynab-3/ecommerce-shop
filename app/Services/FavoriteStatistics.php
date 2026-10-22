<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FavoriteStatistics
{
    public function record(Request $request, int $productId, bool $saved, array $evicted): void
    {
        $visitor = $request->session()->get('favorite_visitor_key');
        if (! $visitor) {
            $visitor = Str::random(64);
            $request->session()->put('favorite_visitor_key', $visitor);
        }
        DB::transaction(function () use ($visitor, $productId, $saved, $evicted) {
            $query = DB::table('product_favorites')->where('visitor_key', $visitor)->where('product_id', $productId);
            $previous = (clone $query)->lockForUpdate()->first();
            if ($previous) {
                $query->update(['is_saved' => $saved, 'save_count' => $previous->save_count + ($saved && ! $previous->is_saved ? 1 : 0), 'updated_at' => now()]);
            } elseif ($saved) {
                DB::table('product_favorites')->insert(['visitor_key' => $visitor, 'product_id' => $productId, 'is_saved' => true, 'save_count' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($evicted) {
                DB::table('product_favorites')->where('visitor_key', $visitor)->whereIn('product_id', $evicted)->update(['is_saved' => false, 'updated_at' => now()]);
            }
        });
    }
}
