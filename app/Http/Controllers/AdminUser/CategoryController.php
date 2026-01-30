<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Requests\AdminUser\CategoryRequest;
use App\Http\Requests\AdminUser\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::orderBy('created_at', 'desc')->get();
        return CategoryResource::collection($categories);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $imagePath = null;

        try {
            DB::beginTransaction(); 

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
                if (!$imagePath) {
                    throw new \Exception("Gagal menyimpan file gambar.");
                }
                $data['image'] = $imagePath;
            }

            $category = Category::create($data);

            DB::commit();
            return (new CategoryResource($category))
                ->additional(['message' => 'Kategori berhasil ditambahkan.'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            DB::rollBack(); 

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            Log::error('Error creating category: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan kategori.',
            ], 500);
        }
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();
        $oldImagePath = $category->image;
        $newImagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('categories', 'public');
                if (!$newImagePath) {
                    throw new \Exception("Gagal menyimpan file gambar baru.");
                }
                $data['image'] = $newImagePath;
            }

            $category->update($data);

            if ($newImagePath && $oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                try {
                    Storage::disk('public')->delete($oldImagePath);
                } catch (\Exception $fileDeleteError) {
                    Log::warning('Gagal menghapus file gambar lama: ' . $oldImagePath . ' Error: ' . $fileDeleteError->getMessage());
                }
            }

            DB::commit();

            // Return resource dengan pesan sukses
            return (new CategoryResource($category->fresh())) // Ambil data terbaru
                ->additional(['message' => 'Kategori berhasil diperbarui.'])
                ->response()
                ->setStatusCode(200);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Hapus file baru yang mungkin sudah terupload jika update DB gagal
            if ($newImagePath && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            Log::error('Error updating category: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui kategori.',
            ], 500);
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        $imagePath = $category->image;

        try {
            DB::beginTransaction();

            $category->delete();

            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                try {
                    Storage::disk('public')->delete($imagePath);
                } catch (\Exception $fileDeleteError) {
                    Log::warning('Gagal menghapus file gambar kategori: ' . $imagePath . ' Error: ' . $fileDeleteError->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Kategori berhasil dihapus.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error deleting category: ' . $e->getMessage(), [
                'category_id' => $category->id,
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus kategori.',
            ], 500);
        }
    }
}
