<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryZone;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeliveryController extends Controller
{
    public function index(Request $r)
    {
        $data = $r->validate(['q' => 'nullable|string|max:100', 'governorate' => 'nullable|string|max:100']);
        $query = DeliveryZone::query();
        if ($r->filled('q')) {
            $query->where('name', 'like', '%'.$data['q'].'%');
        }
        if ($r->filled('governorate')) {
            $query->where('governorate', $data['governorate']);
        }

        return view('admin.delivery', ['zones' => $query->orderBy('governorate')->orderBy('name')->paginate(15)->withQueryString(), 'governorates' => DeliveryZone::distinct()->orderBy('governorate')->pluck('governorate'), 'settings' => StoreSetting::current()]);
    }

    public function update(Request $r)
    {
        $data = $r->validate(['zones' => 'required|array|min:1|max:200', 'zones.*' => 'integer|exists:delivery_zones,id', 'fee' => 'required|decimal:0,2|min:0|max:1000', 'is_active' => 'required|boolean']);
        DeliveryZone::whereIn('id', $data['zones'])->update(['fee_cents' => (int) round((float) $data['fee'] * 100), 'is_active' => (bool) $data['is_active']]);
        AuditLog::record('delivery.bulk_updated', 'zones', ['ids' => $data['zones']]);

        return back()->with('success', 'Delivery areas updated.');
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100|unique:delivery_zones', 'governorate' => 'required|string|max:100', 'fee' => 'sometimes|nullable|decimal:0,2|min:0|max:1000', 'is_active' => 'sometimes|boolean']);
        $zone = DeliveryZone::create(['name' => $data['name'], 'governorate' => $data['governorate'], 'fee_cents' => (int) round((float) ($data['fee'] ?? 0) * 100), 'is_active' => (bool) ($data['is_active'] ?? false)]);
        AuditLog::record('delivery.created', (string) $zone->id);

        return redirect()->route('admin.delivery.index', ['q' => $zone->name])->with('success', 'Delivery area added with its price and availability.');
    }

    public function quick(Request $r, DeliveryZone $zone)
    {
        $data = $r->validate(['fee' => 'required|decimal:0,2|min:0|max:1000', 'is_active' => 'required|boolean']);
        $zone->update(['fee_cents' => (int) round((float) $data['fee'] * 100), 'is_active' => (bool) $data['is_active']]);
        AuditLog::record('delivery.updated', (string) $zone->id);
        if ($r->expectsJson()) {
            return response()->json(['message' => 'Delivery area updated.']);
        }

        return back()->with('success', 'Delivery area updated.');
    }

    public function settings(Request $r)
    {
        $data = $r->validate(['delivery_enabled' => 'required|boolean']);
        StoreSetting::firstOrCreate(['id' => 1])->update($data);
        Cache::forget('store.settings');
        AuditLog::record('delivery.settings_updated', 'store', $data);

        return back()->with('success', 'Checkout delivery pricing updated.');
    }
}
