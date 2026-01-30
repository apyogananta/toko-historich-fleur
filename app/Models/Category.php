<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;
    
    protected $fillable = ['category_name', 'image'];

    // Relasi ke Produk
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = self::generateSlug($category->category_name);
        });

        static::updating(function ($category) {
            $category->slug = self::generateSlug($category->category_name);
        });
    }

    private static function generateSlug($name)
    {
        $slug = Str::slug($name);

        $count = Category::whereRaw("slug RLIKE '^{$slug}(.[0-9]+)?$'")->count();

        return $count ? "{$slug}-{$count}" : $slug;
    }
}
