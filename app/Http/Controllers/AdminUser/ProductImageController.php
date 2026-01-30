<?php

namespace App\Http\Controllers\AdminUser;

use Illuminate\Http\Request;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class ProductImageController extends Controller
{
    public function index()
    {
        $productImages = ProductImage::with('product')->get();
        return response()->json($productImages);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_primary' => 'nullable|boolean',
        ]);

        // Pastikan field images tidak kosong
        if (!$request->hasFile('images')) {
            return response()->json(['error' => 'No images were uploaded'], 400);
        }

        $uploadedImages = [];

        foreach ($request->file('images') as $image) {
            $imagePath = $image->store('product', 'public');

            if ($request->input('is_primary')) {
                ProductImage::where('product_id', $request->product_id)->update(['is_primary' => false]);
            }

            $productImage = ProductImage::create([
                'product_id' => $request->product_id,
                'image' => $imagePath,
                'is_primary' => $request->input('is_primary', false),
            ]);

            $uploadedImages[] = $productImage;
        }

        return response()->json([
            'message' => 'Gambar berhasil diunggah.',
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $productImage = ProductImage::findOrFail($id);

        // Validasi input
        $request->validate([
            'is_primary' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Perbarui status is_primary jika dikirim
        if ($request->has('is_primary') && $request->input('is_primary')) {
            // Pastikan hanya ada satu gambar utama per produk
            ProductImage::where('product_id', $productImage->product_id)->update(['is_primary' => false]);
            $productImage->is_primary = true;
        } elseif ($request->has('is_primary')) {
            // Jika is_primary dikirim tetapi false
            $productImage->is_primary = false;
        }

        // Perbarui gambar jika file dikirim
        if ($request->hasFile('image')) {
            // Hapus file gambar lama
            if ($productImage->image) {
                Storage::disk('public')->delete($productImage->image);
            }

            // Simpan file gambar baru
            $imagePath = $request->file('image')->store('product_images', 'public');
            $productImage->image = $imagePath;
        }

        $productImage->save();

        return response()->json([
            'message' => 'Product image updated successfully.',
            'data' => $productImage,
        ]);
    }

    public function destroy($id)
    {
        $productImage = ProductImage::find($id);

        if (!$productImage) {
            return response()->json([
                'message' => 'Gambar tidak ditemukan.',
            ], 404);
        }

        // Delete the image file
        Storage::disk('public')->delete($productImage->image);

        // Delete the database record
        $productImage->delete();

        return response()->json([
            'message' => 'Gambar berhasil dihapus.',
        ], 200);
    }
}
