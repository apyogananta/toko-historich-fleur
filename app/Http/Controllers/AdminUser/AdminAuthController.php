<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Requests\AdminUser\AdminStoreRequest;
use App\Http\Requests\AdminUser\UpdateSpecificAdminRequest;
use App\Http\Resources\AdminUser\AdminUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUser\AdminLoginRequest;
use App\Http\Requests\AdminUser\UpdateAdminUserRequest;
use App\Models\AdminUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminAuthController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $admins = AdminUser::orderBy('created_at', 'desc')->get();
        return AdminUserResource::collection($admins);
    }

    public function store(AdminStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        AdminUser::create($data);

        return response()->json([
            'message' => 'Admin baru berhasil dibuat.',
        ], 201);
    }

    public function show(Request $request): AdminUserResource
    {
        return new AdminUserResource($request->user());
    }

    public function update(UpdateAdminUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => new AdminUserResource($user),
        ], 200);
    }

    public function destroy(Request $request, AdminUser $admin): JsonResponse
    {
        if ($request->user()->id === $admin->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun sendiri.'
            ], 403); // Forbidden
        }

        $admin->delete();

        return response()->json([
            'message' => 'Admin berhasil dihapus.'
        ], 200);
    }
    public function showSelectedAdmin(AdminUser $admin): AdminUserResource
    {
        return new AdminUserResource($admin);
    }

    public function updateSelectedAdmin(UpdateSpecificAdminRequest $request, AdminUser $admin): AdminUserResource
    {
        $validatedData = $request->validated();

        $admin->update($validatedData);

        return new AdminUserResource($admin);
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = AdminUser::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $token = $user->createToken('admin-auth-token-' . $user->id)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => new AdminUserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ], 200);
    }
}
