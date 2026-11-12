<?php

namespace App\Http\Controllers\Davomat;

use App\Http\Controllers\Controller;
use App\Models\Davomat\DavomatEmployee;
use App\Models\Davomat\DavomatReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRM (crm.zanjeer.uz) bilan almashinuv. CRM ofis tarmog'ida, shuning uchun hamma
 * so'rovni CRM o'zi yuboradi (attendance-bot:sync, har daqiqa):
 *  - PUT /api/davomat/employees — xodimlar ro'yxatini to'liq almashtiradi;
 *  - GET /api/davomat/reasons?after=ID — shu id dan keyingi sabablar.
 */
class CrmSyncController extends Controller
{
    public function employees(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employees' => ['present', 'array'],
            'employees.*.crm_user_id' => ['required', 'integer', 'min:1', 'distinct'],
            'employees.*.name' => ['required', 'string', 'max:255'],
            'employees.*.phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
        ]);

        DB::transaction(function () use ($data): void {
            $ids = array_column($data['employees'], 'crm_user_id');

            DavomatEmployee::query()->whereNotIn('crm_user_id', $ids)->delete();

            foreach ($data['employees'] as $employee) {
                DavomatEmployee::query()->updateOrCreate(
                    ['crm_user_id' => $employee['crm_user_id']],
                    ['name' => $employee['name'], 'phone' => $employee['phone']]
                );
            }
        });

        return response()->json(['count' => count($data['employees'])]);
    }

    public function reasons(Request $request): JsonResponse
    {
        $after = (int) $request->query('after', 0);

        $reasons = DavomatReason::query()
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->map(static fn (DavomatReason $reason): array => [
                'id' => $reason->id,
                'crm_user_id' => $reason->crm_user_id,
                'type' => $reason->type,
                'reason' => $reason->reason,
                'date' => $reason->date->toDateString(),
            ]);

        return response()->json(['reasons' => $reasons]);
    }
}
