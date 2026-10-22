<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = Product::with(['images', 'category'])->orderBy('position')->orderBy('id');
        if ($r->filled('q')) {
            $q->where('name', 'like', '%'.mb_substr($r->string('q'), 0, 100).'%');
        }

        return view('admin.products.index', ['products' => $q->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return $this->form(new Product(['is_active' => true, 'stock_mode' => 'tracked', 'quantity' => 10, 'low_stock_threshold' => 5]));
    }

    public function edit(Product $product)
    {
        $product->load(['images', 'colors']);

        return $this->form($product);
    }

    private function form(Product $product)
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::with('parent')->orderBy('position')->get()]);
    }

    public function store(Request $r, ImageService $images)
    {
        return $this->persist($r, new Product, $images);
    }

    public function update(Request $r, Product $product, ImageService $images)
    {
        return $this->persist($r, $product, $images);
    }

    private function persist(Request $r, Product $product, ImageService $images)
    {
        $data = $r->validate(['stock_revision' => [Rule::requiredIf($product->exists), 'nullable', 'integer', 'min:0'], 'name' => 'required|string|max:180', 'sku' => ['required', 'string', 'max:80', Rule::unique('products')->ignore($product->id)], 'category_id' => 'required|exists:categories,id', 'description' => 'required|string|max:10000', 'price' => 'required|decimal:0,2|min:0|max:1000000', 'compare_price' => 'nullable|decimal:0,2|min:0|max:1000000', 'stock_mode' => 'required|in:tracked,open', 'quantity' => 'required_if:stock_mode,tracked|nullable|integer|min:0|max:10000000', 'low_stock_threshold' => 'nullable|integer|min:0|max:10000000', 'manual_stock_status' => 'required|in:available,low,out', 'is_active' => 'nullable|boolean', 'is_featured' => 'nullable|boolean', 'badge' => 'nullable|string|max:30', 'colors' => 'nullable|array|max:20', 'colors.*.id' => 'nullable|integer', 'colors.*.name' => 'required|string|max:50', 'colors.*.hex' => ['nullable', 'regex:/^#[a-fA-F0-9]{6}$/'], 'images' => 'nullable|array|max:10', 'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:12288']);
        if (($product->exists ? $product->images()->count() : 0) + count($r->file('images', [])) > 10) {
            throw ValidationException::withMessages(['images' => 'A product can have at most 10 images.']);
        }
        $paths = [];
        try {
            foreach ($r->file('images', []) as $file) {
                $paths[] = $images->store($file);
            }
            DB::transaction(function () use ($r, $data, $product, $paths) {
                $isNew = ! $product->exists;
                if (! $isNew) {
                    $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                    if ((int) $product->stock_revision !== (int) $data['stock_revision']) {
                        throw ValidationException::withMessages(['quantity' => 'This product changed after you opened the editor. Reload it before saving to preserve current stock.']);
                    }
                }
                $product->stock_revision++;
                $quantity = $data['stock_mode'] === 'tracked' ? (int) ($data['quantity'] ?? 0) : null;
                $product->fill(collect($data)->only(['name', 'sku', 'category_id', 'description', 'stock_mode', 'manual_stock_status', 'badge'])->all());
                if ($isNew) {
                    $product->slug = Str::slug($data['name']).'-'.Str::lower(Str::random(6));
                }
                $product->price_cents = (int) round((float) $data['price'] * 100);
                $product->compare_price_cents = filled($data['compare_price'] ?? null) ? (int) round((float) $data['compare_price'] * 100) : null;
                $product->quantity = $quantity;
                $product->low_stock_threshold = $data['low_stock_threshold'] ?? Product::defaultThreshold($quantity ?? 0);
                $product->is_active = $r->boolean('is_active');
                $product->is_featured = $r->boolean('is_featured');
                if ($isNew) {
                    $product->position = (Product::max('position') ?? 0) + 1;
                }$product->save();
                $kept = [];
                foreach ($data['colors'] ?? [] as $index => $c) {
                    $color = ! empty($c['id']) ? $product->colors()->findOrFail($c['id']) : $product->colors()->make();
                    $color->fill(['name' => $c['name'], 'hex' => $c['hex'] ?? null, 'position' => $index]);
                    $color->save();
                    $kept[] = $color->id;
                }$product->colors()->whereNotIn('id', $kept)->delete();
                $position = $product->images()->max('position') ?? 0;
                foreach ($paths as $path) {
                    $product->images()->create(['path' => $path, 'alt' => $product->name, 'position' => ++$position]);
                }
                AuditLog::record($isNew ? 'product.created' : 'product.updated', (string) $product->id);
            });
        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                Storage::disk('public')->delete([$path, str_replace('.webp', '-480.webp', $path)]);
            }throw $e;
        }

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product saved. Images are optimized as WebP.');
    }

    public function quick(Request $r, Product $product)
    {
        $data = $r->validate(['is_active' => 'sometimes|boolean', 'delta' => 'sometimes|integer|min:-100000|max:100000', 'quantity' => 'sometimes|integer|min:0|max:10000000|prohibits:delta', 'stock_revision' => 'required_with:quantity|integer|min:0', 'price' => 'sometimes|required|decimal:0,2|min:0|max:1000000', 'compare_price' => 'sometimes|nullable|decimal:0,2|min:0|max:1000000', 'manual_stock_status' => 'sometimes|in:available,low,out']);
        try {
            DB::transaction(function () use ($data, $product) {
                $p = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                if (array_key_exists('quantity', $data)) {
                    abort_unless($p->stock_mode === 'tracked', 422);
                    if ((int) $p->stock_revision !== (int) $data['stock_revision']) {
                        throw ValidationException::withMessages(['quantity' => 'Stock changed while you were editing. The current quantity is shown; enter your new quantity again.']);
                    }
                    $p->quantity = $data['quantity'];
                }
                if (array_key_exists('price', $data)) {
                    $p->price_cents = (int) round((float) $data['price'] * 100);
                }
                if (array_key_exists('compare_price', $data)) {
                    $p->compare_price_cents = filled($data['compare_price']) ? (int) round((float) $data['compare_price'] * 100) : null;
                }
                if (isset($data['delta'])) {
                    abort_unless($p->stock_mode === 'tracked', 422);
                    $next = $p->quantity + $data['delta'];
                    abort_if($next < 0 || $next > 10000000, 422);
                    $p->quantity = $next;
                }if (isset($data['is_active'])) {
                    $p->is_active = $data['is_active'];
                }if (isset($data['manual_stock_status'])) {
                    abort_unless($p->stock_mode === 'open', 422);
                    $p->manual_stock_status = $data['manual_stock_status'];
                }$p->stock_revision++;
                $p->save();
                AuditLog::record('product.quick_update', (string) $p->id, $data);
            });

        } catch (ValidationException $error) {
            if (! $r->expectsJson()) {
                throw $error;
            }

            return response()->json(['message' => $error->getMessage(), 'errors' => $error->errors(), 'product' => $this->snapshot($product->fresh())], 409);
        }

        if ($r->expectsJson()) {
            $p = $product->fresh();

            return response()->json(['message' => 'Product updated.', 'product' => $this->snapshot($p)]);
        }

        return back()->with('success', 'Product updated.');
    }

    private function snapshot(Product $p): array
    {
        return ['id' => $p->id, 'quantity' => $p->quantity, 'stock_revision' => $p->stock_revision, 'stock_status' => $p->stock_status, 'is_active' => $p->is_active, 'price' => number_format($p->price_cents / 100, 2, '.', ''), 'compare_price' => $p->compare_price_cents === null ? '' : number_format($p->compare_price_cents / 100, 2, '.', ''), 'manual_stock_status' => $p->manual_stock_status];
    }

    public function image(Request $r, Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        $data = $r->validate(['product_color_id' => 'nullable|integer', 'position' => 'required|integer|min:0|max:100']);
        if (! empty($data['product_color_id'])) {
            abort_unless($product->colors()->whereKey($data['product_color_id'])->exists(), 422);
        }$image->update($data);

        return back()->with('success', 'Image assignment saved.');
    }

    public function deleteImage(Product $product, ProductImage $image)
    {
        abort_unless($image->product_id === $product->id, 404);
        if (! str_starts_with($image->path, 'images/')) {
            Storage::disk('public')->delete([$image->path, str_replace('.webp', '-480.webp', $image->path)]);
        }$image->delete();

        return back()->with('success', 'Image removed.');
    }
}
