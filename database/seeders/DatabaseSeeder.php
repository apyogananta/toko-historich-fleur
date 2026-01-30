<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteUser;
use App\Models\Address;
use App\Models\AdminUser;
use App\Models\SiteUserAddress;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ShoppingCart;
use App\Models\ShoppingCartItem;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        AdminUser::factory()->create();
        SiteUser::factory()->create();
    }
}
