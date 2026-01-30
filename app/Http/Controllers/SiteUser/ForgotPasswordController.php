<?php

namespace App\Http\Controllers\SiteUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendResetLinkRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(SendResetLinkRequest $request)
    {
        $status = Password::sendResetLink($request->validated());

        return $status == Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset password telah dikirim (jika email terdaftar).'], 200)
            : response()->json(['message' => 'Gagal mengirim link reset. Coba lagi nanti.', 'status_code' => $status], 400);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset($request->validated(), function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();

            $user->tokens()->delete();
        });

        return $status == Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password berhasil diubah.'], 200)
            : response()->json(['message' => 'Gagal mereset password. Token mungkin tidak valid atau sudah kedaluwarsa.', 'status_code' => $status], 400);
    }
}
