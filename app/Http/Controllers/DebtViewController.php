<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Debt;
use Carbon\Carbon;


class DebtViewController extends Controller
{
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

        // 🔍 Xodim yoki kompaniya nomi bo‘yicha qidiruv
        if ($request->filled('employee_name')) {
            $term = '%' . $request->employee_name . '%';
            $query->where(function ($q) use ($term) {
                $q->where('employee_name', 'LIKE', $term)
                  ->orWhere('company_name', 'LIKE', $term);
            });
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

        $totalAmount = (float) (clone $query)->sum('total_amount');

        $debts = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Sabablar (matn / foto / ovoz) bitta so'rovda
        $reasons = DB::table('debt_reasons')
            ->whereIn('debt_id', $debts->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('debt_id');

        $debts->through(function ($debt) use ($reasons) {
            $items = $reasons->get($debt->id, collect());
            $byType = fn (string $type, string $field, string $key) => $items
                ->where('type', $type)
                ->map(fn ($r) => [$key => $r->{$field}, 'created_at' => $r->created_at])
                ->values();

            return [
                'id' => $debt->id,
                'company_name' => $debt->company_name,
                'employee_name' => $debt->employee_name,
                'total_amount' => $debt->total_amount,
                'reasons' => [
                    'texts'  => $byType('text', 'message_text', 'text'),
                    'photos' => $byType('photo', 'file_path', 'file'),
                    'voices' => $byType('voice', 'file_path', 'file'),
                ],
                'updated_at' => $debt->updated_at,
                'created_at' => $debt->created_at,
            ];
        });

        return response()->json([
            'data' => $debts->items(),
            'current_page' => $debts->currentPage(),
            'last_page' => $debts->lastPage(),
            'per_page' => $debts->perPage(),
            'total' => $debts->total(),
            'total_amount' => $totalAmount,
        ]);
    }
}
