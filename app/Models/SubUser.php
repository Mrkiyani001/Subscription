<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubUser extends Model
{
    protected $table = 'user_subscriptions';
    protected $fillable = [
        'user_id',
        'plan_id',
        'session_used',
        'session_remaining',
        'start_date',
        'expiry_date',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plans::class, 'plan_id');
    }

    public function sessions()
    {
        return $this->hasMany(SubSession::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function isActive()
    {
        return $this->status === 'active' && $this->expiry_date >= now()->toDateString();
    }

    public function hasSessionsRemaining()
    {
        return $this->session_remaining > 0;
    }

    public function canUseSession()
    {
        return $this->isActive() && $this->hasSessionsRemaining();
    }
}
