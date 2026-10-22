<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['stock_restored_at' => 'datetime', 'whatsapp_sent_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
