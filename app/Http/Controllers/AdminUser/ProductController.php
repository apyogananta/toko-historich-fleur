<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Requests\AdminUser\StoreProductRequest;
use App\Http\Requests\AdminUser\UpdateProductRequest;
use App\Http\Resources\AdminUser\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'images'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {

        $data = $request->validated();
        $uploadedImagePaths = [];

        try {
            $product = DB::transaction(function () use ($request, $data, &$uploadedImagePaths) {
                $imageData = $data['images'];
                unset($data['images']);

                $product = Product::create($data);

                foreach ($imageData as $index => $imageFile) {
                    $imagePath = $imageFile->store('products', 'public');
                    if (!$imagePath) {
                        throw new \Exception("Gagal menyimpan file gambar index ke-{$index}.");
                    }
                    $uploadedImagePaths[] = $imagePath;

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => $imagePath,
                        'is_primary' => ($index === 0),
                    ]);
                }

                return $product;
            });

            $product->load('images');

            return (new ProductResource($product))
                ->additional(['message' => 'Produk berhasil ditambahkan.'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            foreach ($uploadedImagePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Error creating product: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan produk.',
            ], 500);
        }
    }

    public function show(Product $product): ProductResource
    {
        $product->load(['category', 'images']);
        return new ProductResource($product);
    }

    public function getProductDetail(Product $product): ProductResource // Parameter harus sama dengan nama di route {product}
    {
        $product->load(['category', 'images']);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();
        $uploadedImagePaths = [];
        $deletedImagePaths = [];

        try {
            DB::transaction(function () use ($request, $product, $data, &$uploadedImagePaths, &$deletedImagePaths) {
                $newImageData = $data['images'] ?? [];
                $imagesToDeleteIds = $data['imagesToDelete'] ?? [];
                unset($data['images'], $data['imagesToDelete']);

                $product->update($data);

                if (!empty($imagesToDeleteIds)) {
                    $images = ProductImage::where('product_id', $product->id)
                        ->whereIn('id', $imagesToDeleteIds)
                        ->get();
                    foreach ($images as $img) {
                        $deletedImagePaths[] = $img->image;
                        $img->delete();
                    }
                }

                if (!empty($newImageData)) {
                    $needsNewPrimary = !$product->images()->where('is_primary', true)->exists();

                    foreach ($newImageData as $index => $imageFile) {
                        $imagePath = $imageFile->store('products', 'public');
                        if (!$imagePath) {
                            throw new \Exception("Gagal menyimpan file gambar baru index ke-{$index}.");
                        }
                        $uploadedImagePaths[] = $imagePath;

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image' => $imagePath,
                            'is_primary' => ($index === 0 && $needsNewPrimary),
                        ]);
                        if ($index === 0 && $needsNewPrimary) {
                            $needsNewPrimary = false;
                        }
                    }
                }

                $remainingImages = $product->images()->get();
                if ($remainingImages->isNotEmpty() && !$remainingImages->contains('is_primary', true)) {
                    $firstRemainingImage = $remainingImages->first();
                    $firstRemainingImage->is_primary = true;
                    $firstRemainingImage->save();
                }
            });

            foreach ($deletedImagePaths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Exception $e) {
                        Log::warning("Gagal menghapus file gambar lama saat update produk {$product->id}: {$path}", ['error' => $e->getMessage()]);
                    }
                }
            }

            $product->load('images', 'category');

            return (new ProductResource($product))
                ->additional(['message' => 'Produk berhasil diperbarui.'])
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $e) {
            foreach ($uploadedImagePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Error updating product: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui produk.',
            ], 500);
        }
    }

    public function destroy(Product $product): JsonResponse
    {
        $imagePaths = $product->images()->pluck('image')->toArray();

        try {
            DB::transaction(function () use ($product) {
                $product->delete();
            });

            foreach ($imagePaths as $path) {
                if ($path && Storage::disk('public')->exists($path)) {
                    try {
                        Storage::disk('public')->delete($path);
                    } catch (\Exception $e) {
                        Log::warning("Gagal menghapus file gambar saat delete produk {$product->id}: {$path}", ['error' => $e->getMessage()]);
                    }
                }
            }

            return response()->json(['message' => 'Produk berhasil dihapus.'], 200);
        } catch (\Throwable $e) {
            Log::error('Error deleting product: ' . $e->getMessage(), [
                'product_id' => $product->id,
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus produk.',
            ], 500);
        }
    }
}
