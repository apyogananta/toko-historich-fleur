<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_name',
        'phone_number',
        'address_line1',
        'address_line2',
        'province',
        'city',
        'postal_code',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'site_user_id');
    }
}
