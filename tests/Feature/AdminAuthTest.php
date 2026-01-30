<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario: Admin berhasil login dengan kredensial yang valid.
     */
    public function test_admin_can_login_successfully(): void
    {
        // 1. Arrange: Siapkan data yang dibutuhkan
        $password = 'password123';
        $admin = AdminUser::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make($password),
        ]);

        $payload = [
            'email' => 'admin@test.com',
            'password' => $password,
        ];

        // 2. Act: Kirim request ke endpoint login
        $response = $this->postJson('/api/admin/login', $payload);

        // 3. Assert: Pastikan response sesuai harapan
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'user' => ['id', 'name', 'email'],
                     'token',
                     'token_type',
                 ])
                 ->assertJson([
                     'message' => 'Login berhasil.',
                     'user' => [
                         'id' => $admin->id,
                         'name' => $admin->name,
                         'email' => $admin->email,
                     ]
                 ]);
    }

    /**
     * Skenario: Login gagal karena password yang dimasukkan salah.
     */
    public function test_admin_login_fails_with_wrong_password(): void
    {
        // 1. Arrange
        AdminUser::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password_benar'),
        ]);

        $payload = [
            'email' => 'admin@test.com',
            'password' => 'password_salah',
        ];

        // 2. Act
        $response = $this->postJson('/api/admin/login', $payload);

        // 3. Assert
        $response->assertStatus(401) // Unauthorized
                 ->assertJson([
                     'message' => 'Email atau password salah.',
                 ]);
    }

    /**
     * Skenario: Login gagal karena validasi, email tidak diisi.
     */
    public function test_admin_login_fails_without_email(): void
    {
        // 1. Arrange
        $payload = [
            'password' => 'password123',
        ];

        // 2. Act
        $response = $this->postJson('/api/admin/login', $payload);

        // 3. Assert
        $response->assertStatus(422) // Unprocessable Entity
                 ->assertJsonValidationErrors('email');
    }
}