<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'courier',
        'service',
        'tracking_number',
        'shipping_cost',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

