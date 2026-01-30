<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Observers\ProductObserver;
use App\Policies\SiteUser\ProductReviewPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::except([
            'api/midtrans/notification'
        ]);

        Gate::policy(ProductReview::class, ProductReviewPolicy::class);

        Product::observe(ProductObserver::class);
    }
}
