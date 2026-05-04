<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Register user
     */
    public function register(Request $request)
{
    // 1️⃣ Form validation
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone_number' => 'required',
        'password' => 'required|string|min:6|confirmed', // agar password_confirmation bor bo'lsa
    ]);

    DB::beginTransaction();
    try {
        // 2️⃣ User yaratish
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        // 3️⃣ Role topish yoki yaratish
        $role = Role::firstOrCreate(
            ['name' => 'operation'], // roles tableda name column deb faraz qilaman
        );

        // 4️⃣ Userga role biriktirish
        $user->roles()->attach($role->id);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'User registered and role assigned successfully.',
            'user' => $user
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Login user
     */
     public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Email yoki parol noto‘g‘ri.'
        ], 401);
    }

    // 🔹 Token yaratish
    $token = $user->createToken('auth_token')->plainTextToken;

    // 🔹 Foydalanuvchi rollarini olish
    $roles = $user->roles()->pluck('name'); // ['admin', 'operator']
    return response()->json([
        'message' => 'Login successful',
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $roles
        ]
    ], 200);
}

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}
