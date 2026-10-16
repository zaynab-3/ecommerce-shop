<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreSetting;

class WhatsAppOrderService
{
    public static function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if (! preg_match('/^\+?[0-9\s().-]+$/D', $phone)) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^[1-9][0-9]{6,14}$/D', $digits) ? $digits : null;
    }

    public function url(Order $order): ?string
    {
        $settings = StoreSetting::current();
        $phone = static::normalizePhone((string) $settings->whatsapp_recipient);
        if (! $settings->whatsapp_enabled || ! $phone) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->message($order));
    }

    public function message(Order $order): string
    {
        $order->loadMissing('items');
        $lines = ['Order '.$order->number, '', 'Items:'];
        foreach ($order->items as $index => $item) {
            $lines[] = ($index + 1).'. '.$item->name.' ['.$item->sku.']';
            if ($item->color) {
                $lines[] = '   Color: '.$item->color;
            }
            $lines[] = '   '.$item->quantity.' x '.$this->money($item->price_cents).' = '.$this->money($item->price_cents * $item->quantity);
        }

        $lines = [...$lines, '', 'Customer: '.$order->customer_name, 'Phone: '.$order->phone];
        if ($order->email) {
            $lines[] = 'Email: '.$order->email;
        }
        if ($order->delivery_area) {
            $lines[] = 'Area: '.$order->delivery_area;
        }
        $lines[] = 'Address: '.$order->address;
        if ($order->notes) {
            $lines[] = 'Notes: '.$order->notes;
        }

        return implode("\n", [...$lines, '', 'Subtotal: '.$this->money($order->subtotal_cents), 'Delivery: '.$this->money($order->delivery_cents), 'Total: '.$this->money($order->total_cents), 'Payment: Cash on delivery']);
    }

    private function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 2).' USD';
    }
}
