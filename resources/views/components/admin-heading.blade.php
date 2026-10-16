@props(['eyebrow' => 'YOUR WORKSPACE', 'title', 'description' => null])<div class="admin-page-heading">
    <div><span class="eyebrow muted">{{ $eyebrow }}</span>
        <h1>{{ $title }}<span>.</span></h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    <div>{{ $slot }}</div>
</div>
