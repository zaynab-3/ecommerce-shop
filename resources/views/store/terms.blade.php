@extends('layouts.store')
@section('title', 'Terms & conditions')
@section('content')
<div class="container help-page legal-page">
    <p class="eyebrow">Shopping with us</p>
    <h1>Terms & conditions</h1>
    <p>These terms apply when you shop with {{ \App\Models\StoreSetting::current()->store_name }}. Please review your order and delivery details before placing an order.</p>
    <section>
        <h2>Products and availability</h2>
        <p>Product descriptions, available colours, prices, and stock are shown on the website. Images help you choose a product, but appearance can vary between screens. Stock is checked again when an order is placed. If a product or option becomes unavailable, our team will contact you before arranging a change.</p>
    </section>
    <section>
        <h2>Prices and payment</h2>
        <p>Prices are displayed in US dollars (USD). The checkout review shows your items, applicable delivery fee, and order total before you place the order. Payment is cash on delivery; no card payment is collected on this website. If delivery pricing is disabled, delivery arrangements are confirmed by the team.</p>
    </section>
    <section>
        <h2>Placing an order</h2>
        <p>An order is a request to purchase and is subject to confirmation by our team. An order number confirms that we received your request. Provide an accurate name, reachable phone number, and complete address so we can confirm the order and arrange delivery. If you notice an error, let the team know when they contact you.</p>
        <p>If WhatsApp ordering is offered, the order is saved before WhatsApp opens. Sending the prepared WhatsApp message is a separate step and is not required for the store to receive your order.</p>
    </section>
    <section>
        <h2>Delivery and cancellations</h2>
        <p>Delivery is available to the areas offered at checkout. The team confirms delivery timing and arrangements with you. To request an update or cancellation, contact the team using the details supplied with your order or tell them during the confirmation call. Changes may depend on whether the order has already been dispatched.</p>
    </section>
    <section>
        <h2>Returns and order issues</h2>
        <p>Check your items when they arrive. If an item is damaged, incorrect, or does not match its description, keep the packaging and contact the team with your order number and details of the issue. We will explain the available next steps and any applicable return or exchange conditions before collection. These terms do not limit any rights you have under applicable consumer law.</p>
    </section>
    <section>
        <h2>Privacy and support</h2>
        <p>Our <a href="{{ route('privacy') }}">privacy policy</a> explains how order information and essential cookies are used. For delivery, returns, or an existing order, visit <a href="{{ route('help') }}">Help & support</a>. The terms shown here apply to new orders placed while they are published.</p>
    </section>
    <a class="button button-dark" href="{{ route('shop') }}">Back to shop <x-icon name="arrow" /></a>
</div>
@endsection
