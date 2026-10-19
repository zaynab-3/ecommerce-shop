@props(['eyebrow' => 'YOUR WORKSPACE', 'title', 'description' => null])<div class="admin-page-heading">
    <div><h1>{{ $title }}</h1>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    <div>{{ $slot }}</div>
</div>
