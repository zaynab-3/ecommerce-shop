@props(['type', 'id'])<div class="reorder-buttons">
    @foreach (['up' => '↑', 'down' => '↓'] as $direction => $label)
        <form method="post" action="{{ route('admin.sort', [$type, $id]) }}">@csrf<input type="hidden" name="direction"
                value="{{ $direction }}"><button aria-label="Move {{ $direction }}"
                title="Move {{ $direction }}">{{ $label }}</button></form>
    @endforeach
</div>
