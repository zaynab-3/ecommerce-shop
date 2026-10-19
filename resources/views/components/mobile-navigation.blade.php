<dialog id="mobile-navigation" class="mobile-navigation" aria-label="Main navigation">
    <div class="drawer-header">
        <a class="logo" href="{{ route('home') }}">{{ \App\Models\StoreSetting::current()->store_name }}<span class="logo-dot">.</span></a>
        <button type="button" class="icon-button drawer-close" data-menu-close aria-label="Close navigation" autofocus><x-icon name="close" /></button>
    </div>
    <nav class="drawer-content" aria-label="Shop navigation"><x-store-navigation-links :drawer="true" /></nav>
</dialog>
