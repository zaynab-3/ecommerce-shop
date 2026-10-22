<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    public function update(Order $order, string $status, ?int $assignee): void
    {
        app(TransactionService::class)->run(function () use ($order, $status, $assignee) {
            $order = Order::with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (auth()->user()?->role === 'orders') {
                abort_if($order->assigned_to && $order->assigned_to !== auth()->id(), 403);
                abort_unless($assignee === auth()->id(), 403);
            }
            $transitions = ['pending' => ['pending', 'confirmed', 'cancelled'], 'confirmed' => ['confirmed', 'processing', 'cancelled'], 'processing' => ['processing', 'shipped', 'cancelled'], 'shipped' => ['shipped', 'delivered'], 'delivered' => ['delivered'], 'cancelled' => ['cancelled']];
            if (! in_array($status, $transitions[$order->status], true)) {
                throw ValidationException::withMessages(['status' => 'This order cannot move to that status.']);
            }
            if ($status === 'cancelled' && ! $order->stock_restored_at) {
                $this->restoreStock($order);
            }
            $order->status = $status;
            $order->assigned_to = $assignee;
            $order->save();
            AuditLog::record('order.updated', $order->number, ['status' => $status, 'assigned_to' => $assignee]);
        });
    }

    public function delete(Order $order): void
    {
        abort_unless(auth()->user()?->role === 'owner', 403);
        app(TransactionService::class)->run(function () use ($order) {
            $order = Order::withTrashed()->with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->trashed()) {
                return;
            }
            $previousStatus = $order->status;
            if (in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
                if (! $order->stock_restored_at) {
                    $this->restoreStock($order);
                }
                $order->status = 'cancelled';
                $order->save();
            }
            $order->delete();
            AuditLog::record('order.deleted', $order->number, ['previous_status' => $previousStatus, 'status' => $order->status, 'history_retained' => true]);
            DB::afterCommit(fn () => CatalogRevision::publish());
        });
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items->where('tracked_stock', true)->sortBy('product_id') as $item) {
            Product::whereKey($item->product_id)->where('stock_mode', 'tracked')->increment('quantity', $item->quantity, ['stock_revision' => DB::raw('stock_revision + 1')]);
        }
        $order->stock_restored_at = now();
    }
}
