<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Beri role default
        $user->assignRole('Customer');

        // Trigger event untuk kirim email verifikasi
        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // 2. LOGIN (With Account Lock, Rate Limiter & History)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Cek Account Lock
        if ($user && $user->is_locked) {
            return response()->json(['message' => 'Akun Anda terkunci karena terlalu banyak percobaan login.'], 403);
        }

        // Cek Rate Limiter (Brute Force Protection)
        $throttleKey = 'login:'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            if ($user) {
                $user->update(['is_locked' => true, 'locked_at' => now()]);
            }
            return response()->json(['message' => 'Terlalu banyak percobaan. Akun Anda telah dikunci.'], 429);
        }

        // Verifikasi Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            return response()->json(['message' => 'Email atau Password salah.'], 401);
        }

        RateLimiter::clear($throttleKey);

        // Catat Login History
        DB::table('login_histories')->insert([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login sukses.',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    // 3. LOGOUT
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    // 4. FORGOT PASSWORD (Kirim link reset)
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
                    ? response()->json(['message' => 'Link reset password telah dikirim ke email.'])
                    : response()->json(['message' => 'Email tidak ditemukan.'], 404);
    }

    // 5. RESET PASSWORD
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), 
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(\Illuminate\Support\Str::random(60));
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? response()->json(['message' => 'Password berhasil direset.'])
                    : response()->json(['message' => 'Token tidak valid atau kadaluarsa.'], 400);
    }

    // 6. RESEND EMAIL VERIFICATION
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi sebelumnya.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link verifikasi telah dikirim ulang.']);
    }
}