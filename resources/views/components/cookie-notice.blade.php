@php($notice = \App\Http\Controllers\CookieNoticeController::class)
<aside class="cookie-notice" data-cookie-notice aria-labelledby="cookie-notice-title" @if(request()->cookie($notice::COOKIE) === $notice::VERSION) hidden @endif>
    <form method="post" action="{{ route('cookies.acknowledge') }}" data-cookie-form>
        @csrf<input type="hidden" name="version" value="{{ $notice::VERSION }}">
        <div class="cookie-notice-heading">
            <h2 id="cookie-notice-title">A little about cookies</h2>
            <button class="icon-button" aria-label="Dismiss cookie notice"><x-icon name="close" /></button>
        </div>
        <p>We use essential cookies to remember your cart and saved items, keep checkout secure, and remember this notice. We don’t use advertising or analytics cookies.</p>
        <div class="cookie-notice-actions"><button class="button button-dark">Got it</button><a href="{{ route('privacy') }}#cookies">Cookie details</a></div>
        <p data-cookie-error role="alert" hidden></p>
    </form>
</aside>
