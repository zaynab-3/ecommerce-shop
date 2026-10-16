@props(['url', 'autoOpen' => false])
<section class="whatsapp-handoff" aria-labelledby="whatsapp-heading">
    <h2 id="whatsapp-heading">Your order, ready for WhatsApp</h2>
    <p>Your order is saved. Review the message in WhatsApp and tap <strong>Send</strong> to share it with our team.</p>
    <a class="button button-dark" href="{{ $url }}" rel="noreferrer" data-whatsapp-order @if($autoOpen) data-whatsapp-auto-open @endif>Open WhatsApp <x-icon name="arrow" /></a>
    <p class="summary-note">If WhatsApp doesn’t open, use the button above. Your order is already in our system.</p>
</section>
