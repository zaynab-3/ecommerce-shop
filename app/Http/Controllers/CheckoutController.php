<?php

namespace App\Http\Controllers;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\StoreSetting;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\WhatsAppOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(CartService $cart)
    {
        $lines = $cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('cart.index');
        }
        $key = session('checkout_key');
        if (! $key || $key === session('checkout_completed_key')) {
            session()->put('checkout_key', (string) Str::uuid());
        }

        return view('store.checkout', ['lines' => $lines, 'subtotal' => $lines->sum('total'), 'settings' => StoreSetting::current(), 'zones' => DeliveryZone::where('is_active', true)->orderBy('governorate')->orderBy('name')->get()]);
    }

    public function store(Request $r, CheckoutService $checkout)
    {
        $data = $r->validate(['idempotency_key' => 'required|uuid', 'customer_name' => 'required|string|max:120', 'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,25}$/'], 'email' => 'nullable|email|max:190', 'address' => 'required|string|min:8|max:1000', 'delivery_zone_id' => 'nullable|integer', 'notes' => 'nullable|string|max:1000']);
        abort_unless(hash_equals((string) session('checkout_key'), $data['idempotency_key']), 419);
        $order = $checkout->place($data, session('cart', []));
        session(['cart' => [], 'checkout_completed_key' => $data['idempotency_key'], 'order_access' => array_slice(array_unique([...session('order_access', []), $order->public_id]), -20)]);
        session()->flash('whatsapp_order', $order->public_id);

        return redirect()->route('orders.confirmation', $order->public_id);
    }

    public function confirmation(Request $request, Order $order, WhatsAppOrderService $whatsapp)
    {
        abort_unless(in_array($order->public_id, session('order_access', []), true), 404);
        $order->load('items');
        $whatsappUrl = $whatsapp->url($order);
        $autoOpenWhatsApp = $request->session()->pull('whatsapp_order') === $order->public_id && $whatsappUrl !== null;

        return view('store.confirmation', compact('order', 'whatsappUrl', 'autoOpenWhatsApp'));
    }
}
