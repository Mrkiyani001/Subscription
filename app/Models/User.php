<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Subscription Relationships
    public function sessionSubscriptions()
    {
        return $this->hasMany(SubUser::class, 'user_id');
    }

    public function sessions()
    {
        return $this->hasMany(SubSession::class, 'user_id');
    }

    // Helper Methods
    public function activeSubscription()
    {
        return $this->sessionSubscriptions()
            ->where('status', 'active')
            ->where('expiry_date', '>=', now()->toDateString())
            ->first();
    }

    public function hasActiveSubscription()
    {
        return $this->activeSubscription() !== null;
    }

    public function canUseSession()
    {
        $subscription = $this->activeSubscription();
        return $subscription && $subscription->canUseSession();
    }
}
