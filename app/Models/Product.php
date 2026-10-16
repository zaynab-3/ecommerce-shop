<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean', 'quantity' => 'integer', 'price_cents' => 'integer', 'low_stock_threshold' => 'integer'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class)->orderBy('position')->orderBy('id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->whereHas('category', fn ($c) => $c->visible());
    }

    public function getStockStatusAttribute(): string
    {
        return $this->stock_mode === 'open' ? $this->manual_stock_status : ($this->quantity <= 0 ? 'out' : ($this->quantity <= $this->low_stock_threshold ? 'low' : 'available'));
    }

    public function getImageUrlAttribute(): string
    {
        return $this->images->first()?->url ?? asset('images/placeholder.svg');
    }

    public static function defaultThreshold(int $qty): int
    {
        return $qty <= 10 ? 5 : ($qty <= 30 ? 10 : ($qty <= 100 ? 15 : (int) ceil($qty * .15)));
    }
}
