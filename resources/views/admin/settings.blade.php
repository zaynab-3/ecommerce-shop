@extends('layouts.admin')
@section('title', 'Store settings')
@section('content')<x-admin-heading title="Store settings" />
    <form method="post" action="{{ route('admin.settings.update') }}" class="settings-form">@csrf @method('PATCH')<section
            class="admin-panel form-panel">
            <h2>Store details</h2><label class="field">Store name<input name="store_name"
                    value="{{ old('store_name', $settings->store_name) }}" maxlength="80" required></label>
        </section>
        <section class="admin-panel form-panel">
            <h2>Website appearance</h2>
            <label class="field">Main color<input class="theme-color-picker" name="accent_color" type="color" value="{{ old('accent_color', $settings->accentColor()) }}" required><small>One accent color, black, and white. Used for primary actions and selected options.</small></label>
        </section>
        <section class="admin-panel form-panel">
            <div class="setting-toggle-row">
                <div><x-icon name="truck" />
                    <h2>Delivery pricing</h2>
                    <p>Enable area selection and delivery fees at checkout.</p>
                </div><label class="switch"><input type="hidden" name="delivery_enabled" value="0"><input
                        name="delivery_enabled" type="checkbox" value="1"
                        @checked(old('delivery_enabled', $settings->delivery_enabled))><span></span><span class="sr-only">Enable delivery
                        pricing</span></label>
            </div><a class="text-link" href="{{ route('admin.delivery.index') }}">Manage delivery areas <x-icon
                    name="arrow" width="15" /></a>
        </section>
        <section class="admin-panel form-panel">
            <div class="setting-toggle-row">
                <div><x-icon name="bag" />
                    <h2>WhatsApp orders & questions</h2>
                    <p>Enable WhatsApp checkout and product questions.</p>
                </div><label class="switch"><input name="whatsapp_enabled" type="hidden" value="0"><input
                        name="whatsapp_enabled" type="checkbox" value="1"
                        @checked(old('whatsapp_enabled', $settings->whatsapp_enabled))><span></span><span class="sr-only">Enable WhatsApp
                        checkout</span></label>
            </div><label class="field">Recipient phone number<input name="whatsapp_recipient"
                    value="{{ old('whatsapp_recipient', $settings->whatsapp_recipient) }}" placeholder="+961 70 123 456"
                    type="tel" inputmode="tel" maxlength="30"><small>Include the country code, for example +961 70 123 456.</small></label>
            <p class="form-hint">The customer reviews the WhatsApp message and taps Send.</p>
        </section><button class="button button-dark">Save settings <x-icon name="check" /></button>
</form>@endsection
