<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Exports\AttendanceExport;
use App\Exports\DailyAttendanceExport;
use App\Services\Attendance\DailyAttendanceReport;
use Carbon\Carbon;
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

    /**
     * Kunlik davomat holati (kelgan / kech qolgan / hali kelmagan / kelmagan / sababli / erta ketgan)
     */
    public function daily(Request $request, DailyAttendanceReport $report)
    {
        return response()->json($report->build($this->dailyDate($request)));
    }

    /**
     * Kunlik holatni Excel'ga — paneldagi filtrlar (kartochka, bo'lim, qidiruv) bilan
     */
    public function exportDaily(Request $request, DailyAttendanceReport $report)
    {
        $request->validate([
            'filter' => ['nullable', 'in:all,present,late,missing,excused,early'],
            'department' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $report->build($this->dailyDate($request));
        $rows = DailyAttendanceReport::filter($data['employees'], $request->filter, $request->department, $request->q);

        return Excel::download(new DailyAttendanceExport($data, $rows), 'davomat_' . $data['date'] . '.xlsx');
    }

    private function dailyDate(Request $request): Carbon
    {
        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        return $request->filled('date') ? Carbon::createFromFormat('Y-m-d', $request->date) : today();
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
