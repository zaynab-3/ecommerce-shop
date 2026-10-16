<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StoreSetting;
use App\Services\WhatsAppOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings', ['settings' => StoreSetting::current()]);
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'store_name' => 'required|string|max:80',
            'delivery_enabled' => 'required|boolean',
            'whatsapp_enabled' => 'required|boolean',
            'whatsapp_recipient' => ['nullable', Rule::requiredIf($r->boolean('whatsapp_enabled')), 'string', 'max:30'],
        ]);
        $phone = filled($data['whatsapp_recipient'] ?? null) ? WhatsAppOrderService::normalizePhone($data['whatsapp_recipient']) : null;
        if (filled($data['whatsapp_recipient'] ?? null) && ! $phone) {
            throw ValidationException::withMessages(['whatsapp_recipient' => 'Enter a valid phone number with its country code, for example +961 70 123 456.']);
        }
        $data['whatsapp_recipient'] = $phone;
        StoreSetting::firstOrCreate(['id' => 1])->update($data);
        Cache::forget('store.settings');
        AuditLog::record('settings.updated', 'store');

        return back()->with('success', 'Store settings saved.');
    }
}
