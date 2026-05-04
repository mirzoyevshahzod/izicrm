<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DriversController extends Controller
{
    /**
     * Barcha haydovchilar ma'lumotlarini olish
     */
   public function index()
{
    // Barcha driverlar va ularning fayllarini olish
    $drivers = Driver::with('files')->get(); 

    return response()->json([
        'success' => true,
        'data' => $drivers
    ], 200);
}


    /**
     * Chat ID bo'yicha bitta haydovchi ma'lumotini olish
     */
    public function show($chat_id)
    {
        $driver = Driver::where('chat_id', $chat_id)->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver topilmadi'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $driver
        ], 200);
    }

        public function getOperatorById($id)
{
    // ID bo‘yicha operatorni topish
    $operator = User::where('id', $id)
        ->whereHas('roles', function ($q) {
            $q->where('name', 'operation');
        })
        ->first();

    if (!$operator) {
        return response()->json([
            'status' => 'error',
            'message' => 'Operator not found'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'operator' => $operator
    ]);
}

public function updateOperator(Request $request, $id)
{
    // 1️⃣ Validatsiya
    $request->validate([
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:users,email,' . $id,
        'phone_number' => 'nullable|string|max:20',
        'password' => 'nullable|string|min:6',
    ]);

    // 2️⃣ Operatorni topish (faqat operation role bilan)
    $operator = User::where('id', $id)
        ->whereHas('roles', function ($q) {
            $q->where('name', 'operation');
        })
        ->first();

    if (!$operator) {
        return response()->json([
            'status' => 'error',
            'message' => 'Operator not found'
        ], 404);
    }

    // 3️⃣ Yangilash
    if ($request->name) {
        $operator->name = $request->name;
    }
    if ($request->email) {
        $operator->email = $request->email;
    }
    if ($request->phone_number) {
        $operator->phone_number = $request->phone_number;
    }
    if ($request->password) {
        $operator->password = Hash::make($request->password);
    }

    $operator->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Operator updated successfully',
        'operator' => $operator
    ]);
}



    public function getOperationUsers()
{
    // 'roles' relation orqali 'operation' roliga ega userlarni olamiz
    $users = User::whereHas('roles', function ($query) {
        $query->where('name', 'operation');
    })->get();

    return response()->json([
        'status' => 'success',
        'users' => $users
    ]);
}

public function profile(Request $request)
{
    // Login bo‘lgan userni olish
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated.'
        ], 401);
    }

     $user->load('roles');

    return response()->json([
        'status' => 'success',
        'user' => $user
    ]);
}

public function deleteOperator($id)
{
    DB::beginTransaction();
    try {
        // 1️⃣ Operatorni topish
        $user = User::whereHas('roles', function ($query) {
            $query->where('name', 'operation');
        })->findOrFail($id);

        // 2️⃣ Pivot table (role_user) dagi bog‘lanishni o‘chirish
        $user->roles()->detach();

        // 3️⃣ Userni o‘chirish
        $user->delete();

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Operator muvaffaqiyatli o\'chirildi.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function searchByPhone(Request $request)
{
    $request->validate([
        'phone' => 'required|string'
    ]);

    $phone = $request->phone;

    // Telefon raqam bo‘yicha qidirish
    $drivers = Driver::where('phone', 'LIKE', "%{$phone}%")->get();

    return response()->json([
        'success' => true,
        'count' => $drivers->count(),
        'data' => $drivers
    ]);
}

}
