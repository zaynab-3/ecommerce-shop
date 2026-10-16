<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['metadata' => 'array'];

    public static function record(string $action, string $subject, array $metadata = []): void
    {
        static::create(['user_id' => auth()->id(), 'action' => $action, 'subject' => $subject, 'metadata' => $metadata]);
    }
}
