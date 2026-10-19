@props(['category'])
<details class="category-icon-editor"><summary>Change icon</summary>
<form method="post" enctype="multipart/form-data" action="{{ route('admin.categories.update', $category) }}">@csrf @method('PATCH')
<label class="field">Library icon<select name="icon">@foreach(['grid','headphones','home','bag','sparkles','gamepad','watch','gem'] as $name)<option value="{{ $name }}" @selected($category->icon === $name)>{{ ucfirst($name) }}</option>@endforeach</select></label>
<label class="field">SVG icon<input type="file" name="icon_file" accept="image/svg+xml,.svg"><small>Simple vector icon, up to 128 KB.</small></label>
@if($category->icon_path)<label class="checkbox-field"><input type="checkbox" name="remove_icon" value="1">Use library icon instead</label>@endif
<button class="button button-dark">Save icon</button>
</form></details>
