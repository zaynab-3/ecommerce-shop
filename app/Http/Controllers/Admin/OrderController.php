<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $data = $r->validate(['status' => 'nullable|in:pending,confirmed,processing,shipped,delivered,cancelled', 'q' => 'nullable|string|max:100', 'mine' => 'nullable|boolean']);
        $q = Order::with('assignee')->latest();
        if ($r->filled('status')) {
            $q->where('status', $data['status']);
        }if ($r->filled('q')) {
            $q->where(fn ($q) => $q->where('number', 'like', '%'.$data['q'].'%')->orWhere('customer_name', 'like', '%'.$data['q'].'%'));
        }if ($r->boolean('mine')) {
            $q->where('assigned_to', auth()->id());
        }

        return view('admin.orders.index', ['orders' => $q->paginate(20)->withQueryString()]);
    }

    public function show(Order $order)
    {
        $order->load(['items', 'assignee']);

        return view('admin.orders.show', ['order' => $order, 'admins' => User::where('is_active', true)->get(['id', 'name'])]);
    }

    public function update(Request $r, Order $order, OrderStatusService $service)
    {
        $data = $r->validate(['status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled', 'assigned_to' => 'nullable|exists:users,id']);
        $assignee = filled($data['assigned_to'] ?? null) ? (int) $data['assigned_to'] : null;
        if (auth()->user()->role === 'orders') {
            abort_if($order->assigned_to && $order->assigned_to !== auth()->id(), 403);
            abort_unless($assignee === auth()->id(), 403);
        }if ($assignee) {
            abort_unless(User::whereKey($assignee)->where('is_active', true)->exists(), 422);
        }$service->update($order, $data['status'], $assignee);

        return back()->with('success', 'Order updated.');
    }
}
