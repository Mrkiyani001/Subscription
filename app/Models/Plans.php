<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plans extends Model
{
    protected $table = 'subscription_plans';
    protected $fillable = [
        'name',
        'slug',
        'description',
        'session_limit',
        'duration_days',
        'price',
        'is_active',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function userSubscriptions()
    {
        return $this->hasMany(SubUser::class, 'plan_id');
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active === true;
    }

    public function activeSubscriptionsCount()
    {
        return $this->userSubscriptions()->where('status', 'active')->count();
    }
}
