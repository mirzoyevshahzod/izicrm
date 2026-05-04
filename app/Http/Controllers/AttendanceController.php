<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function attendanceLogin(Request $request)
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

    public function attendance(){
        $attendance = Attendance::query()
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $attendance
        ]);
    }

    public function searchAttendance(Request $request)
    {
        $query = Attendance::query()->orderBy('year', 'desc')
                    ->orderBy('month', 'desc')
                    ->orderBy('day', 'desc');

        // 🔍 FIO bo‘yicha qidiruv
        if ($request->filled('fio')) {

            $names = array_filter(array_map('trim', explode(',', $request->fio)));

            $query->where(function ($q) use ($names) {
                foreach ($names as $name) {
                    $q->orWhere('fio', 'LIKE', "%{$name}%");
                }
            });
        }

        // 📅 Sana bo‘yicha qidiruv (kun / oy / yil alohida)
        if ($request->filled('day')) {
            $query->where('day', (int)$request->day);
        }

        if ($request->filled('month')) {
            $query->where('month', (int)$request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', (int)$request->year);
        }

        $attendances = $query
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('day', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'filters' => $request->only(['fio', 'day', 'month', 'year']),
            'data' => $attendances
        ]);
    }

    public function exportAttendance(Request $request)
    {
        $filters = $request->only(['fio', 'day', 'month', 'year']);

        return Excel::download(
            new AttendanceExport($filters),
            'attendances.xlsx'
        );
    }
}
