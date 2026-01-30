<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Requests\AddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->authorizeResource(Address::class, 'address');
    }

    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->latest()->get();

        return AddressResource::collection($addresses);
    }

    public function store(AddressRequest $request)
    {
        $user = $request->user();
        $validatedData = $request->validated();

        if ($request->input('is_default', false)) {
            $user->addresses()->update(['is_default' => false]);
            $validatedData['is_default'] = true;
        } else {
            $validatedData['is_default'] = false;
        }

        $address = $user->addresses()->create($validatedData);

        return response()->json([
            'message' => 'Alamat berhasil ditambahkan.',
            'address' => new AddressResource($address),
        ], 201);
    }

    public function show(Address $address)
    {
        return new AddressResource($address);
    }

    public function update(AddressRequest $request, Address $address)
    {
        $user = $request->user();
        $validatedData = $request->validated();

        if ($request->input('is_default', false)) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $validatedData['is_default'] = true;
        } else {
            $isTryingToUnset = $request->has('is_default') && !$request->input('is_default');
            $isLastAddress = $user->addresses()->count() <= 1;

            if ($isTryingToUnset && $isLastAddress) {
                return response()->json([
                    'message' => 'Tidak dapat mengubah status default alamat terakhir.',
                    'errors' => ['is_default' => ['Alamat terakhir harus menjadi alamat default.']]
                ], 422);
            } else {
                $validatedData['is_default'] = $request->input('is_default', $address->is_default);
            }
        }

        $address->update($validatedData);

        return response()->json([
            'message' => 'Alamat berhasil diperbarui.',
            'address' => new AddressResource($address->fresh()),
        ], 200);
    }

    public function destroy(Request $request, Address $address)
    {
        $user = $request->user();

        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault && $user->addresses()->exists()) {
            $newDefaultAddress = $user->addresses()->oldest()->first();
            if ($newDefaultAddress) {
                $newDefaultAddress->update(['is_default' => true]);
                Log::info("Address ID {$newDefaultAddress->id} set as new default for User ID {$user->id}");
            }
        }

        return response()->json(['message' => 'Alamat berhasil dihapus.'], 200);
    }
}
