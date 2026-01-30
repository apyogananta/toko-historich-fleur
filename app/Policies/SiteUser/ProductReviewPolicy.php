<?php

namespace App\Policies\SiteUser;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SiteUser;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProductReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(?SiteUser $siteUser)
    {
        return true;
    }

    public function view(?SiteUser $siteUser, ProductReview $productReview)
    {
        return true;
    }

    public function create(SiteUser $siteUser, Product $product)
    {
        $hasPurchasedAndDelivered = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) use ($siteUser) {
                $query->where('site_user_id', $siteUser->id)
                    ->whereHas('shipment', function ($shipmentQuery) {
                        $shipmentQuery->where('status', 'delivered');
                    });
            })
            ->exists();

        if (!$hasPurchasedAndDelivered) {
            return $this->deny('Anda harus membeli produk ini dan pesanan harus sudah diterima untuk memberikan review.');
        }

        $hasAlreadyReviewed = ProductReview::where('site_user_id', $siteUser->id)
            ->where('product_id', $product->id)
            ->exists();

        if ($hasAlreadyReviewed) {
            return $this->deny('Anda sudah memberikan review untuk produk ini.');
        }

        return true;
    }

    public function update(SiteUser $siteUser, ProductReview $productReview)
    {
        return $siteUser->id === $productReview->site_user_id
            ? $this->allow()
            : $this->deny('Anda tidak memiliki izin untuk mengubah review ini.');
    }

    public function delete(SiteUser $siteUser, ProductReview $productReview)
    {
        return $siteUser->id === $productReview->site_user_id
            ? $this->allow()
            : $this->deny('Anda tidak memiliki izin untuk menghapus review ini.');
    }

    public function checkEligibility(SiteUser $siteUser, Product $product): bool
    {
        $hasPurchasedAndDelivered = OrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) use ($siteUser) {
                $query->where('site_user_id', $siteUser->id)
                    ->whereHas('shipment', function ($shipmentQuery) {
                        $shipmentQuery->where('status', 'delivered');
                    });
            })
            ->exists();

        if (!$hasPurchasedAndDelivered) {
            return false;
        }

        $hasAlreadyReviewed = ProductReview::where('site_user_id', $siteUser->id)
            ->where('product_id', $product->id)
            ->exists();

        return !$hasAlreadyReviewed;
    }
}
