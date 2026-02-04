<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubSession extends Model
{
    protected $table = 'subscription_sessions';
    protected $fillable = [
        'user_id',
        'session_type',
        'used_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subscription()
    {
        return $this->belongsTo(SubUser::class, 'user_id', 'user_id')
            ->where('status', 'active');
    }
}
