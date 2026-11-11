<?php

namespace App\Http\Controllers;

use App\Models\AttendanceEmployee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Face ID xodimlari (attendance_employees) — panel CRUD (permission: employees.manage).
 * person_id Face ID qurilmasidagi "Идентификатор" bilan bir xil bo'lishi kerak:
 * davomat yozuvlari xodimga shu ID orqali bog'lanadi.
 */
class AttendanceEmployeeController extends Controller
{
    public function index()
    {
        $employees = AttendanceEmployee::orderBy('first_name')->orderBy('last_name')->get();

        return response()->json([
            'employees' => $employees->map(fn (AttendanceEmployee $e) => $this->payload($e)),
            'departments' => $employees->pluck('department')->filter()->unique()->sort()->values(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = AttendanceEmployee::create($this->validated($request));

        return response()->json($this->payload($employee), 201);
    }

    public function update(Request $request, AttendanceEmployee $attendanceEmployee)
    {
        $data = $this->validated($request, $attendanceEmployee);

        // ID boshqa odamga o'tganda eski odamning Telegram bog'lanishini uzish uchun
        if ($request->boolean('unlink_telegram')) {
            $data['chat_id'] = null;
        }

        $attendanceEmployee->update($data);

        return response()->json($this->payload($attendanceEmployee->fresh()));
    }

    public function destroy(AttendanceEmployee $attendanceEmployee)
    {
        $attendanceEmployee->delete();

        return response()->json(['message' => __('Xodim o‘chirildi.')]);
    }

    private function validated(Request $request, ?AttendanceEmployee $employee = null): array
    {
        $data = $request->validate([
            'person_id' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('attendance_employees', 'person_id')->ignore($employee?->id)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:32',
            'is_active' => 'boolean',
        ], [
            'person_id.required' => __('Face ID dagi ID ni kiriting.'),
            'person_id.unique' => __('Bu ID bilan xodim allaqachon bor.'),
            'person_id.regex' => __('ID faqat harf va raqamlardan iborat bo‘lsin.'),
            'first_name.required' => __('Ismni kiriting.'),
        ]);

        return array_map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v, $data);
    }

    private function payload(AttendanceEmployee $e): array
    {
        return [
            'id' => $e->id,
            'person_id' => $e->person_id,
            'first_name' => $e->first_name,
            'last_name' => $e->last_name,
            'department' => $e->department,
            'phone' => $e->phone,
            'chat_id' => $e->chat_id,
            'is_active' => (bool) $e->is_active,
            'created_at' => $e->created_at,
        ];
    }
}
