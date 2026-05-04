<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters)
    {
        $this->filters = $filters;
    }

   public function collection()
{
    $query = Attendance::query();

    if (!empty($this->filters['fio'])) {
        $query->where('fio', 'LIKE', '%' . $this->filters['fio'] . '%');
    }

    if (!empty($this->filters['day'])) {
        $query->where('day', $this->filters['day']);
    }

    if (!empty($this->filters['month'])) {
        $query->where('month', $this->filters['month']);
    }

    if (!empty($this->filters['year'])) {
        $query->where('year', $this->filters['year']);
    }

    return $query->get();
}

    public function map($attendance): array
    {
        return [
            $attendance->id,
            $attendance->chat_id,
            $attendance->fio,
            $attendance->group,

            // 🔥 DATE BIRLASHTIRISH
            sprintf(
                '%02d.%02d.%04d',
                $attendance->day,
                $attendance->month,
                $attendance->year
            ),

            $attendance->reason,
            $attendance->late_minutes,
            optional($attendance->created_at)->format('Y-m-d H:i'),
        ];
    }
    public function headings(): array
    {
        return [
            'ID',
            'Chat ID',
            'FIO',
            'Group',
            'Date', // 🔥 endi bitta column
            'Reason',
            'Late Minutes',
            'Created At'
        ];
    }
}