@extends('layouts.store')
@section('title', 'Help & delivery')
@section('content')<div class="container help-page"><h1>Help & delivery</h1>
        <section id="delivery">
            <h2>Delivery information</h2>
            <p>We deliver to the areas available in the checkout area selector. Select your area to see the exact delivery
                fee before ordering. When delivery fees are disabled, the team will confirm delivery arrangements with you.
                Delivery timing is confirmed by our team after your order is placed.</p>
        </section>
        <section id="returns">
            <h2>Returns & exchanges</h2>
            <p>Please inspect your items when they arrive. If something is damaged, incorrect, or does not meet the product
                description, keep the packaging and contact our team using the contact details supplied with your order. We
                will confirm the next steps and any applicable return conditions before collection.</p>
        </section>
        <section id="contact">
            <h2>Orders & support</h2>
            <p>Keep your order number after checkout. Our team will call the phone number you provided to confirm your order
                and delivery details. To update or cancel an order, tell the team when they contact you. No payment is
                collected online.</p>
        </section>
        <section id="privacy">
            <h2>Privacy & shopping terms</h2>
            <p>We use your name, contact details, address, and order information to process and deliver your purchase. Store
                staff can access this information for order fulfillment. If WhatsApp checkout is enabled, WhatsApp opens with your order details addressed to the store. Review the message and tap Send to share it through WhatsApp. Your order is saved in our system even if you do not send the message.</p>
            <p>Your shopping cart, saved items, and order confirmation access use a session cookie. Prices are in USD. Stock
                and delivery fees are checked again when your order is placed. An order is a request to purchase, subject to
                confirmation by our team. These operational terms should be reviewed and completed with the business’s legal
                identity, contact details, return periods, and local legal requirements before launch.</p>
        </section><a class="button button-dark" href="{{ route('shop') }}">Back to shop <x-icon name="arrow" /></a>
</div>@endsection
