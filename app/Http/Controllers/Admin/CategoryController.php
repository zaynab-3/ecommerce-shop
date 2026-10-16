<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories', ['categories' => Category::with(['children' => fn ($q) => $q->withCount('products')])->whereNull('parent_id')->withCount('products')->orderBy('position')->get()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'parent_id' => 'nullable|exists:categories,id', 'icon' => 'nullable|in:grid,headphones,home,bag,sparkles,gamepad,watch']);
        if (! empty($data['parent_id'])) {
            abort_if(Category::findOrFail($data['parent_id'])->parent_id, 422);
        }Category::create([...$data, 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(5)), 'position' => (Category::max('position') ?? 0) + 1]);
        Cache::forget('catalog.categories');

        return back()->with('success', 'Category created.');
    }

    public function update(Request $r, Category $category)
    {
        $data = $r->validate(['name' => 'sometimes|required|string|max:100', 'is_active' => 'sometimes|boolean']);
        $category->update($data);
        Cache::forget('catalog.categories');
        AuditLog::record('category.updated', (string) $category->id, $data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->withErrors(['category' => 'Move its products and subcategories before deleting this category.']);
        }$category->delete();
        Cache::forget('catalog.categories');

        return back()->with('success', 'Category deleted.');
    }
}
