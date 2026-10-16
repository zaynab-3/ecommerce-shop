<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryZone;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index()
    {
        return view('admin.delivery', ['zones' => DeliveryZone::orderBy('governorate')->orderBy('name')->get()->groupBy('governorate')]);
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
        $data = $r->validate(['name' => 'required|string|max:100|unique:delivery_zones', 'governorate' => 'required|string|max:100']);
        DeliveryZone::create($data);

        return back()->with('success', 'Delivery area added. Select it to set its price and availability.');
    }
}
