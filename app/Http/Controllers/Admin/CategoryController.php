<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Services\CategoryIconService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::with(['children' => fn ($q) => $q->withCount('products')])->whereNull('parent_id')->withCount('products')->orderBy('position')->get()]);
    }

    public function store(Request $r, CategoryIconService $icons)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'parent_id' => 'nullable|exists:categories,id', 'icon' => 'nullable|in:grid,headphones,home,bag,sparkles,gamepad,watch,gem', 'icon_file' => 'nullable|file|mimetypes:image/svg+xml,text/xml,application/xml,text/plain|max:128']);
        if (! empty($data['parent_id'])) {
            abort_if(Category::findOrFail($data['parent_id'])->parent_id, 422);
        }
        unset($data['icon_file']);
        if ($r->hasFile('icon_file')) $data['icon_path'] = $icons->store($r->file('icon_file'));
        Category::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)), 'position' => (Category::max('position') ?? 0) + 1]);
        Cache::forget('catalog.categories');

        return back()->with('success', 'Category created.');
    }

    public function update(Request $r, Category $category, CategoryIconService $icons)
    {
        $data = $r->validate(['name' => 'sometimes|required|string|max:100', 'is_active' => 'sometimes|boolean', 'icon' => 'sometimes|nullable|in:grid,headphones,home,bag,sparkles,gamepad,watch,gem', 'icon_file' => 'nullable|file|mimetypes:image/svg+xml,text/xml,application/xml,text/plain|max:128', 'remove_icon' => 'sometimes|boolean']);
        $oldIcon = $category->icon_path;
        unset($data['icon_file'], $data['remove_icon']);
        if ($r->boolean('remove_icon')) $data['icon_path'] = null;
        if ($r->hasFile('icon_file')) $data['icon_path'] = $icons->store($r->file('icon_file'));
        $category->update($data);
        if ($oldIcon && $oldIcon !== $category->icon_path) Storage::disk('local')->delete($oldIcon);
        Cache::forget('catalog.categories');
        AuditLog::record('category.updated', (string) $category->id, $data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Move its products and subcategories before deleting this category.']);
        }$category->delete();
        if ($category->icon_path) Storage::disk('local')->delete($category->icon_path);
        Cache::forget('catalog.categories');

        return back()->with('success', 'Category deleted.');
    }
}
