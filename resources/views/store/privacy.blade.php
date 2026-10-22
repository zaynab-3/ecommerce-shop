@extends('layouts.store')
@section('title', 'Privacy policy')
@section('content')
<div class="container help-page legal-page">
    <p class="eyebrow">Your information</p>
    <h1>Privacy policy</h1>
    <p>This policy explains how {{ \App\Models\StoreSetting::current()->store_name }} uses information when you browse, save products, or place an order.</p>
    <section>
        <h2>Information we collect</h2>
        <p>At checkout we collect your name, phone number, delivery address, selected area, order items, and any notes you choose to provide. An email address is not required. Your cart and saved items are linked to your browser session. Technical information such as IP addresses and request logs may be used to keep the service secure and diagnose problems.</p>
    </section>
    <section>
        <h2>How we use and share it</h2>
        <p>We use your information to confirm your order, check stock, arrange delivery, provide support, and prevent misuse. Store staff responsible for your order can access it. We share the details needed for delivery with the people handling your delivery. We do not sell your personal information.</p>
        <p>The store owner can view sales, order, stock, and saved-product totals to understand demand and manage inventory. Saved-product statistics use a random browser identifier held in your existing shopping session, without your name, contact details, or cross-site tracking. Clearing that session starts a new browser count.</p>
        <p>If you choose a WhatsApp ordering or enquiry option, you are taken to WhatsApp with a prepared message. Review the message before sending it. WhatsApp processes information under its own privacy policy. Your order is saved by the store before WhatsApp opens, even if you do not send the message.</p>
    </section>
    <section id="cookies">
        <h2>Cookies, simply explained</h2>
        <p>A cookie is a small piece of information your browser keeps for a website. Our essential cookies maintain your session so your cart, saved items, and access to your order confirmation keep working as you move between pages. A security cookie helps protect forms against forged requests. These are needed for the shopping features; they do not track you across other websites.</p>
        <p>When you dismiss the cookie notice, we also store an acknowledgement for 180 days so we do not show it on every visit. It contains the notice version, not your name or order details. This site does not currently use advertising or analytics cookies.</p>
        <p>You can reopen the notice using <a href="#cookies" data-cookie-settings>Cookie settings</a> or the link in the footer. You can delete or block cookies in your browser settings. Blocking essential cookies can prevent the cart, saved items, and checkout from working. Clearing them also resets your notice acknowledgement.</p>
    </section>
    <section>
        <h2>Storage and security</h2>
        <p>Order information is kept as needed to fulfil purchases, resolve support requests, and meet applicable recordkeeping obligations. Access to store management is restricted to authorised staff. No system can guarantee absolute security; please avoid putting sensitive information in order notes.</p>
    </section>
    <section>
        <h2>Your information and questions</h2>
        <p>To ask about your information, request a correction, or request deletion where applicable, contact the store team using the contact details supplied with your order. Keep your order number so we can find the right record. Some order records may need to be retained for legal or accounting purposes. See <a href="{{ route('help') }}#contact">Orders & support</a> for help with an existing order.</p>
    </section>
    <p>See also our <a href="{{ route('terms') }}">terms & conditions</a>.</p>
    <a class="button button-dark" href="{{ route('shop') }}">Back to shop <x-icon name="arrow" /></a>
</div>
@endsection
