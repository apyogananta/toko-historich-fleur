<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'site_user_id',
        'address_id',
        'order_number',
        'total_amount',
        'shipping_cost',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'site_user_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
}
