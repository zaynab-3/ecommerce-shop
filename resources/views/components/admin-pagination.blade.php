@props(['items'])@if ($items->hasPages())
    <nav class="pagination" aria-label="Pages">
        @if ($items->previousPageUrl())
        <a href="{{ $items->previousPageUrl() }}">← Previous</a>@else<span>← Previous</span>
        @endif
        <span>
            {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
        @if ($items->nextPageUrl())
        <a href="{{ $items->nextPageUrl() }}">Next →</a>@else<span>Next →</span>
        @endif
    </nav>
@endif
