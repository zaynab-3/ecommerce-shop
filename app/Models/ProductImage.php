<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $guarded = [];

    public function getUrlAttribute(): string
    {
        return str_starts_with($this->path, 'images/') ? asset($this->path) : asset('storage/'.$this->path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return str_starts_with($this->path, 'images/') ? $this->url : asset('storage/'.str_replace('.webp', '-480.webp', $this->path));
    }

    public function color()
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }
}
