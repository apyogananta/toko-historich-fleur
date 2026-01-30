# Online Clothing Store RESTful API

A production-ready RESTful API for an online clothing store built with Laravel 11 (PHP 8.2+), featuring full product catalog, powerful Elasticsearch-based search, Midtrans payment integration, RajaOngkir shipping cost calculation, token-based authentication with Laravel Sanctum, shopping cart, orders, shipments, product reviews, recommendations, and an admin dashboard.

---

## Key Features
- Product catalog with categories, images, stock, pricing (original/sale), brands, colors, and slugs.
- Fast product search powered by Elasticsearch with custom analyzers and synonyms (see [config/elasticsearch_mappings.php](config/elasticsearch_mappings.php)); products are auto-synced via observer: [`App\Observers\ProductObserver`](app/Observers/ProductObserver.php).
- Secure authentication/authorization using Laravel Sanctum (see [config/sanctum.php](config/sanctum.php)).
- Shopping cart, orders, and shipments.
- Payment integration with Midtrans (Snap + notifications) (see [config/midtrans.php](config/midtrans.php), [`App\Http\Controllers\SiteUser\PaymentController`](app/Http/Controllers/SiteUser/PaymentController.php)).
- Domestic shipping cost estimation via RajaOngkir proxy API (see [`App\Http\Controllers\SiteUser\ShipmentController`](app/Http/Controllers/SiteUser/ShipmentController.php)).
- Product reviews with policies and resources.
- Admin dashboard with sales, orders, and user summaries.
- Robust validation, resources, caching (database), and queue (database) configuration (see [config/cache.php](config/cache.php), [config/queue.php](config/queue.php)).

---

## Requirements
- PHP 8.2+
- Composer
- Database: SQLite/MySQL/MariaDB/PostgreSQL (default connection is SQLite; see [config/database.php](config/database.php))
- Elasticsearch (compatible with mappings in [config/elasticsearch_mappings.php](config/elasticsearch_mappings.php))
- Midtrans account (server/client key)
- RajaOngkir API key (and origin postal code)

---

## Getting Started

1) Clone and install
- Copy environment variables:
  - cp .env.example .env (see [.env.example](.env.example))
- Install dependencies:
  - composer install
- Generate application key:
  - php artisan key:generate

2) Configure .env
- App/URL, database (see [config/database.php](config/database.php))
- Sanctum/cookies/session if needed (see [config/sanctum.php](config/sanctum.php), [config/session.php](config/session.php))
- Elasticsearch host(s) (see config file at [config/elasticsearch.php](config/elasticsearch.php))
- Midtrans:
  - MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, MIDTRANS_IS_PRODUCTION, MIDTRANS_IS_SANITIZED, MIDTRANS_IS_3DS (see [config/midtrans.php](config/midtrans.php))
  - Optional: NGROK_HTTP_8000 for override notification URL (used by [`App\Http\Controllers\SiteUser\PaymentController`](app/Http/Controllers/SiteUser/PaymentController.php))
- RajaOngkir:
  - RAJA_ONGKIR_API_KEY, POSTAL_CODE_ORIGIN (used by [`App\Http\Controllers\SiteUser\ShipmentController`](app/Http/Controllers/SiteUser/ShipmentController.php))
- Mailer settings for password reset (see [config/mail.php](config/mail.php))

3) Database and storage
- Migrate:
  - php artisan migrate
- Seed demo data (products, categories, images):
  - php artisan db:seed --class=Database\\Seeders\\CategorySeeder (see [database/seeders/CategorySeeder.php](database/seeders/CategorySeeder.php))
  - php artisan db:seed --class=Database\\Seeders\\ProductSeeder (see [database/seeders/ProductSeeder.php](database/seeders/ProductSeeder.php))
- Link storage for public images:
  - php artisan storage:link

4) Elasticsearch
- Ensure your cluster is running and create the index using the mappings in [config/elasticsearch_mappings.php](config/elasticsearch_mappings.php).
- Existing products can be bulk indexed using the included console command (see [`App\Console\Commands\IndexProductsElasticsearch`](app/Console/Commands/IndexProductsElasticsearch.php) for the command signature).

5) Run the app
- php artisan serve

Optional background workers
- Clear/optimize cache: php artisan cache:clear, php artisan config:cache

---

## Core Modules (entry points)
- Search: [`App\Http\Controllers\SiteUser\ProductSearchController`](app/Http/Controllers/SiteUser/ProductSearchController.php)
- Auth (site users): [`App\Http\Controllers\SiteUser\AuthController`](app/Http/Controllers/SiteUser/AuthController.php)
- Password reset: [`App\Http\Controllers\SiteUser\ForgotPasswordController`](app/Http/Controllers\SiteUser/ForgotPasswordController.php)
- Cart and orders: requests in [app/Http/Requests](app/Http/Requests) (e.g., [`App\Http\Requests\AddToCartRequest`](app/Http/Requests/AddToCartRequest.php), [`App\Http\Requests\StoreOrderRequest`](app/Http/Requests/StoreOrderRequest.php))
- Payments: [`App\Http\Controllers\SiteUser\PaymentController`](app/Http/Controllers/SiteUser/PaymentController.php)
- Shipping: [`App\Http\Controllers\SiteUser\ShipmentController`](app/Http/Controllers/SiteUser/ShipmentController.php)
- Product reviews: [`App\Http\Controllers\SiteUser\ProductReviewController`](app/Http/Controllers/SiteUser/ProductReviewController.php)
- Admin: categories/products/dashboard:
  - [`App\Http\Controllers\AdminUser\CategoryController`](app/Http/Controllers/AdminUser/CategoryController.php)
  - [`App\Http\Controllers\AdminUser\ProductController`](app/Http/Controllers/AdminUser/ProductController.php)
  - [`App\Http\Controllers\AdminUser\DashboardController`](app/Http/Controllers/AdminUser/DashboardController.php)

---

## Notes
- Product indexing to Elasticsearch is handled automatically on create/update/delete via [`App\Observers\ProductObserver`](app/Observers/ProductObserver.php).
- Payment notifications are excluded from CSRF by a global exception in [`App\Providers\AppServiceProvider`](app/Providers/AppServiceProvider.php).
- The project uses database cache/queue drivers by default; adjust in [config/cache.php](config/cache.php) and [config/queue.php](config/queue.php) as needed.
