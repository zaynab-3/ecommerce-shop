<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeVisible($q)
    {
        return $q->where('is_active', true)->where(fn ($q) => $q->whereNull('parent_id')->orWhereHas('parent', fn ($p) => $p->where('is_active', true)));
    }
}
