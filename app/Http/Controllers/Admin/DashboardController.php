<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', ['orders' => Order::with('assignee')->latest()->limit(6)->get(), 'pending' => Order::where('status', 'pending')->count(), 'sales' => Order::where('status', '!=', 'cancelled')->whereDate('created_at', today())->sum('total_cents'), 'productCount' => Product::count(), 'lowStock' => Product::where('stock_mode', 'tracked')->whereColumn('quantity', '<=', 'low_stock_threshold')->count()]);
    }
}
