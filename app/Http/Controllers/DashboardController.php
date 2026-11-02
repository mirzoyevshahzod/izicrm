<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Debt;
use App\Models\HhSearchResume;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Bosh sahifadagi qisqa ko'rsatkichlar — faqat foydalanuvchi ko'ra oladigan bo'limlar bo'yicha
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $today = now();
        $stats = [];

        if ($user->can('attendance.view')) {
            $todayQuery = Attendance::where('day', $today->day)
                ->where('month', $today->month)
                ->where('year', $today->year);

            $stats['attendance'] = [
                'today' => (clone $todayQuery)->count(),
                'today_minutes' => (int) (clone $todayQuery)->sum('late_minutes'),
                'month' => Attendance::where('month', $today->month)->where('year', $today->year)->count(),
            ];
        }

        if ($user->can('debts.view')) {
            $stats['debts'] = [
                'count' => Debt::count(),
                'total' => (float) Debt::sum('total_amount'),
            ];
        }

        if ($user->can('hh.view')) {
            $stats['hh'] = [
                'new' => HhSearchResume::where('status', 'new')
                    ->whereHas('search', fn ($q) => $q->where('is_active', true))->count(),
                'selected' => HhSearchResume::where('status', 'selected')->count(),
            ];
        }

        if ($user->can('users.manage')) {
            $stats['users'] = [
                'count' => User::whereNotNull('email')->count(),
            ];
        }

        return response()->json($stats);
    }
}
