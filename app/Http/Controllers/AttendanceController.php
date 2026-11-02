<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
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
