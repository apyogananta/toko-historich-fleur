<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShoppingCartItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['shopping_cart_id', 'product_id', 'qty'];

    // Relasi ke ShoppingCart
    public function shoppingCart()
    {
        return $this->belongsTo(ShoppingCart::class, 'shopping_cart_id');
    }

    // Relasi ke Produk
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
