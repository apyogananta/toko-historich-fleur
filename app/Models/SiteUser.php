<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\SiteUserResetPasswordNotification;

class SiteUser extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, Notifiable, HasApiTokens, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'phone_number'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'
        ];
    }

    public function shoppingCart()
    {
        return $this->hasOne(ShoppingCart::class, 'site_user_id');
    }

    public function addresses()
    {
        return $this->hasMany(Address::class, 'site_user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'site_user_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $frontendUrl = 'http://localhost:5173';
        $url = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($this->email);

        $this->notify(new SiteUserResetPasswordNotification($url));
    }
}