<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'site_user_id',
        'product_id',
        'rating',
        'review',
    ];

    public function user()
    {
        return $this->belongsTo(SiteUser::class, 'site_user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

