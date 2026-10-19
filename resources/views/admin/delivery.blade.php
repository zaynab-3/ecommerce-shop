@extends('layouts.admin')
@section('title', 'Delivery areas')
@section('content')<x-admin-heading title="Delivery areas" />
    <div class="admin-form-layout">
        <div class="admin-form-primary">
            <form method="post" action="{{ route('admin.delivery.update') }}" class="admin-panel form-panel">@csrf
                @method('PATCH')<div class="panel-heading compact">
                    <div>
                        <h2>Delivery areas</h2>
                        <p>Only enabled areas are available at checkout.</p>
                    </div><label class="checkbox-field"><input type="checkbox" data-select-all="zones[]"> Select all</label>
                </div>
                <div class="bulk-delivery-controls"><label class="field">Delivery price (USD)<input name="fee"
                            type="number" min="0" max="1000" step="0.01" value="{{ old('fee', '3.00') }}"
                            required></label><label class="field">Availability<select name="is_active">
                            <option value="1">Delivery available</option>
                            <option value="0">Unavailable</option>
                        </select></label><button class="button button-dark">Apply to selected <x-icon
                            name="check" /></button></div>
                @foreach ($zones as $gov => $areas)
                    <section class="zone-group">
                        <div class="zone-heading">
                            <h3>{{ $gov }}</h3><button type="button" class="text-link"
                                data-select-group="{{ $loop->index }}">Select group</button>
                        </div>
                        <div class="zone-grid" data-zone-group="{{ $loop->index }}">
                            @foreach ($areas as $zone)
                                <label class="zone-card"><input name="zones[]" type="checkbox"
                                        value="{{ $zone->id }}"><span><strong>{{ $zone->name }}</strong><small>{{ $zone->is_active ? '$' . number_format($zone->fee_cents / 100, 2) : 'Not available' }}</small></span><span
                                        class="zone-dot {{ $zone->is_active ? 'enabled' : '' }}"></span></label>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </form>
        </div>
        <aside class="admin-form-secondary">
            <section class="admin-panel form-panel"><x-icon name="truck" width="30" />
                <h2 class="spaced-title">Delivery settings</h2>
                <p class="panel-description">Use select-all, a governorate group, or individual areas. Apply one price and
                    availability setting to your selection.</p>
                <p class="panel-description">The store-wide delivery fee switch is in Store settings. Fees are calculated
                    again on the server during checkout.</p><a class="text-link" href="{{ route('delivery.areas') }}"
                    target="_blank" rel="noopener">Delivery areas JSON API <x-icon name="arrow" width="15" /></a>
            </section>
            <form method="post" action="{{ route('admin.delivery.store') }}" class="admin-panel form-panel">@csrf<h2>Add
                    an area</h2>
                <div class="field-grid single"><label class="field">Area or town<input name="name" required
                            maxlength="100"></label><label class="field">Governorate<input name="governorate"
                            list="governorates" required maxlength="100"><datalist id="governorates">
                            @foreach ($zones->keys() as $gov)
                                <option value="{{ $gov }}">
                            @endforeach
                        </datalist></label><button class="button button-light">Add delivery area <x-icon
                            name="plus" /></button></div>
            </form>
        </aside>
</div>@endsection
