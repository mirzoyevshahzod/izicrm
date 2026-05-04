<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Debt;
use Carbon\Carbon;


class DebtViewController extends Controller
{
   public function index()
    {
        $debts = DB::table('debts')
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($debts as $debt) {

            // Shu qarzga tegishli barcha sabablar
            $reasons = DB::table('debt_reasons')
                ->where('debt_id', $debt->id)
                ->orderBy('created_at')
                ->get();

            $texts  = [];
            $photos = [];
            $voices = [];

            foreach ($reasons as $reason) {
                if ($reason->type === 'text') {
                    $texts[] = [
                        'text' => $reason->message_text,
                        'created_at' => $reason->created_at,
                    ];
                }

                if ($reason->type === 'photo') {
                    $photos[] = [
                        'file' => $reason->file_path,
                        'created_at' => $reason->created_at,
                    ];
                }

                if ($reason->type === 'voice') {
                    $voices[] = [
                        'file' => $reason->file_path,
                        'created_at' => $reason->created_at,
                    ];
                }
            }

            $result[] = [
                'company_name' => $debt->company_name,
                'employee_name' => $debt->employee_name,
                'total_amount' => $debt->total_amount,
                'reasons' => [
                    'texts'  => $texts,
                    'photos' => $photos,
                    'voices' => $voices,
                ],
                'updated_at' => $debt->updated_at,
                'created_at' => $debt->created_at
            ];
        }

        return response()->json($result);
    }

    public function search(Request $request)
    {
        // ✅ Validatsiya
        $request->validate([
            'employee_name' => 'nullable|string|max:255',
            'date'          => 'nullable|date',
            'from'          => 'nullable|date',
            'to'            => 'nullable|date',
        ]);

        $query = Debt::query();

        // 🔍 Xodim ismi bo‘yicha qidiruv
        if ($request->filled('employee_name')) {
            $query->where('employee_name', 'LIKE', '%' . $request->employee_name . '%');
        }

        // 📅 Bitta sana bo‘yicha (created_at)
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // 📅 Sana oralig‘i bo‘yicha (from → to)
        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay(),
            ]);
        }

        $debts = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20);

            $result = [];

        foreach ($debts as $debt) {

            // Shu qarzga tegishli barcha sabablar
            $reasons = DB::table('debt_reasons')
                ->where('debt_id', $debt->id)
                ->orderBy('created_at')
                ->get();

            $texts  = [];
            $photos = [];
            $voices = [];

            foreach ($reasons as $reason) {
                if ($reason->type === 'text') {
                    $texts[] = [
                        'text' => $reason->message_text,
                        'created_at' => $reason->created_at,
                    ];
                }

                if ($reason->type === 'photo') {
                    $photos[] = [
                        'file' => $reason->file_path,
                        'created_at' => $reason->created_at,
                    ];
                }

                if ($reason->type === 'voice') {
                    $voices[] = [
                        'file' => $reason->file_path,
                        'created_at' => $reason->created_at,
                    ];
                }
            }

            $result[] = [
                'company_name' => $debt->company_name,
                'employee_name' => $debt->employee_name,
                'total_amount' => $debt->total_amount,
                'reasons' => [
                    'texts'  => $texts,
                    'photos' => $photos,
                    'voices' => $voices,
                ],
                'updated_at' => $debt->updated_at,
                'created_at' => $debt->created_at
            ];
        }

        return response()->json($result);
    }


    public function debtLogin(Request $request)
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

}
