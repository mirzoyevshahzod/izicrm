<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Panelga kirish (yagona login: davomat, qarzdorlik va boshqalar uchun)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email yoki parol noto‘g‘ri.'], 401);
        }

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type' => 'Bearer',
            'user' => self::userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Joriy foydalanuvchi va u ko'ra oladigan bo'limlar (frontend menyusi shundan quriladi)
     */
    public function me(Request $request)
    {
        return response()->json(self::userPayload($request->user()));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password:sanctum',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'Joriy parol noto‘g‘ri.',
            'password.min' => 'Yangi parol kamida 8 belgidan iborat bo‘lsin.',
            'password.confirmed' => 'Parollar mos kelmadi.',
        ]);

        $user = $request->user();
        $user->password = $request->password;
        $user->save();

        // Boshqa qurilmalardagi sessiyalarni yopamiz, joriysi qoladi
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Parol yangilandi.']);
    }

    public static function userPayload(User $user): array
    {
        $isAdmin = $user->hasRole('admin');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'is_admin' => $isAdmin,
            'permissions' => $isAdmin
                ? array_keys(config('panel.permissions'))
                : $user->getAllPermissions()->pluck('name')->values()->all(),
            'created_at' => $user->created_at,
        ];
    }
}
