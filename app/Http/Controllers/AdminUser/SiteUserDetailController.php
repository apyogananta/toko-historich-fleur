<?php

namespace App\Http\Controllers\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUser\SiteUserResource;
use App\Models\SiteUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class SiteUserDetailController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SiteUser::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        $users = $query->paginate(15)->withQueryString();

        return SiteUserResource::collection($users);
    }

    public function show(SiteUser $siteUser): SiteUserResource
    {
        $siteUser->load(['addresses', 'orders.orderItems']);

        return new SiteUserResource($siteUser);
    }

    public function updateStatus(Request $request, SiteUser $siteUser): JsonResponse
    {
        $data = $request->validate([
            'is_active' => 'required|boolean'
        ], [
            'is_active.required' => 'Status akun wajib diisi.',
            'is_active.boolean' => 'Status akun harus bernilai true atau false (1 atau 0).',
        ]);

        $siteUser->update(['is_active' => $data['is_active']]);

        return response()->json([
            'message' => 'Status akun pengguna berhasil diperbarui.',
            'user' => new SiteUserResource($siteUser->fresh()),
        ], 200);
    }
}
