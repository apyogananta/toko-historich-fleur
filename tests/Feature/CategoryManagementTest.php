<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\AdminUser;
use App\Models\Category;
use Laravel\Sanctum\Sanctum;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Buat fake storage disk 'public' untuk testing
        Storage::fake('public');
    }

    /**
     * Skenario: Admin yang sudah login berhasil menambahkan kategori baru.
     */
    public function test_admin_can_add_new_category(): void
    {
        // 1. Arrange
        // Buat admin user
        $admin = AdminUser::factory()->create();
        
        // Buat token untuk admin tersebut
        $token = $admin->createToken('test-token')->plainTextToken;

        // Siapkan data payload
        $payload = [
            'category_name' => 'Celana Jeans',
            'image' => UploadedFile::fake()->image('jeans.jpg', 100, 100)->size(50),
        ];

        // 2. Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/admin/category', $payload);

        // 3. Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('categories', [
            'category_name' => 'Celana Jeans'
        ]);

        $category = Category::first();
        $this->assertTrue(Storage::disk('public')->exists($category->image));
    }

    /**
     * Skenario: Pengguna yang belum login tidak bisa menambahkan kategori.
     */
    public function test_unauthenticated_user_cannot_add_category(): void
    {
        // 1. Arrange
        $payload = [
            'category_name' => 'Jaket',
            'image' => UploadedFile::fake()->image('jacket.jpg'),
        ];

        // 2. Act
        $response = $this->postJson('/api/admin/category', $payload);

        // 3. Assert
        $response->assertStatus(401); // Unauthorized
    }
}