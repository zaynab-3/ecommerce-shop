@if (session('success'))
    <div class="notice success" role="status">
        <x-icon name="check" /><span>{{ session('success') }}</span><button type="button" data-dismiss
            aria-label="Dismiss notification"><x-icon name="close" /></button>
    </div>
@endif
@if ($errors->any())
    <div class="notice error" role="alert">
        <div><strong>Please check the following:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
