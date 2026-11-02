<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel foydalanuvchilarini boshqarish (permission: users.manage)
 */
class UserController extends Controller
{
    public function index()
    {
        // Panel foydalanuvchilari — email bilan kiradiganlar (botlar foydalanuvchilari emas)
        $users = User::whereNotNull('email')
            ->with('roles', 'permissions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'users' => $users->map(fn (User $u) => AuthController::userPayload($u)),
            'permissions' => config('panel.permissions'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = new User();
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => $data['password'],
        ])->save();

        $this->syncAccess($user, $data);

        return response()->json(AuthController::userPayload($user->fresh()), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && empty($data['is_admin']) && $user->hasRole('admin')) {
            return response()->json(['message' => 'O‘zingizdan admin huquqini olib bo‘lmaydi.'], 422);
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
        ]);
        if (!empty($data['password'])) {
            $user->password = $data['password'];
            $user->tokens()->delete(); // parol almashtirilsa, eski sessiyalar yopiladi
        }
        $user->save();

        $this->syncAccess($user, $data);

        return response()->json(AuthController::userPayload($user->fresh()));
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return response()->json(['message' => 'O‘zingizni o‘chira olmaysiz.'], 422);
        }

        $user->tokens()->delete();
        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->delete();

        return response()->json(['message' => 'Foydalanuvchi o‘chirildi.']);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone_number' => 'nullable|string|max:32',
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_admin' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in(array_keys(config('panel.permissions')))],
        ], [
            'email.unique' => 'Bu email bilan foydalanuvchi allaqachon bor.',
            'password.required' => 'Parol kiriting.',
            'password.min' => 'Parol kamida 8 belgidan iborat bo‘lsin.',
            'permissions.*.in' => 'Noma’lum ruxsat tanlangan.',
        ]);
    }

    private function syncAccess(User $user, array $data): void
    {
        $user->syncRoles(!empty($data['is_admin']) ? ['admin'] : []);
        // Admin hamma narsani ko'radi, unga alohida ruxsat shart emas
        $user->syncPermissions(!empty($data['is_admin']) ? [] : ($data['permissions'] ?? []));
    }
}
