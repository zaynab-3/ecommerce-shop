@if (session('success') || $errors->any())
<div class="toast-stack" data-toast-stack>
    @if (session('success'))<div class="toast notice success" data-toast role="status"><x-icon name="check" /><span>{{ session('success') }}</span><button type="button" data-dismiss aria-label="Dismiss notification"><x-icon name="close" /></button></div>@endif
    @if ($errors->any())<div class="toast notice error" data-toast role="alert"><span>{{ implode(' ', $errors->all()) }}</span><button type="button" data-dismiss aria-label="Dismiss notification"><x-icon name="close" /></button></div>@endif
</div>
@endif
