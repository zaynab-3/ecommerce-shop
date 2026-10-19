<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\WhatsAppOrderService;
use App\Support\LebanesePhone;
use Closure;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index(CartService $cart)
    {
        $data = $cart->viewData();
        if ($data['lines']->isEmpty()) {
            return redirect()->route('cart.index');
        }
        return view('store.checkout', $data);
    }

    public function store(Request $r, CheckoutService $checkout)
    {
        $data = $r->validate(['idempotency_key' => 'required|uuid', 'customer_name' => 'required|string|max:120', 'phone' => ['required', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value) || ! LebanesePhone::normalize($value)) {
                $fail('Enter a valid Lebanese phone number, for example 03 123 456 or 70 123 456.');
            }
        }], 'address' => 'required|string|min:8|max:1000', 'delivery_zone_id' => 'nullable|integer', 'notes' => 'nullable|string|max:1000']);
        $data['phone'] = LebanesePhone::normalize($data['phone']);
        abort_unless(hash_equals((string) session('checkout_key'), $data['idempotency_key']), 419);
        $order = $checkout->place($data, session('cart', []));
        session(['cart' => [], 'checkout_completed_key' => $data['idempotency_key'], 'order_access' => array_slice(array_unique([...session('order_access', []), $order->public_id]), -20)]);
        session()->flash('whatsapp_order', $order->public_id);

        if ($r->expectsJson()) {
            $order->refresh()->load('items');
            $whatsappUrl = app(WhatsAppOrderService::class)->url($order);
            session()->forget('whatsapp_order');

            return response()->json([
                'html' => view('store.partials.confirmation', ['order' => $order, 'whatsappUrl' => $whatsappUrl, 'autoOpenWhatsApp' => false])->render(),
                'count' => 0,
                'confirmation_url' => route('orders.confirmation', $order->public_id),
                'whatsapp_url' => $whatsappUrl,
            ]);
        }

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
