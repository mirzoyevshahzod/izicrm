<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Kunlik davomat holati (panel → Davomat → Kunlik holat) Excel ko'rinishida:
 * tepada qisqa xulosa, pastda xodimlar jadvali (paneldagi filtrlar bilan).
 */
class DailyAttendanceExport implements FromArray, WithStyles, WithTitle, WithEvents, ShouldAutoSize, WithStrictNullComparison
{
    private const STATUS_COLORS = [
        'on_time' => 'DCFCE7',
        'late' => 'FEF3C7',
        'not_arrived' => 'F1F5F9',
        'absent' => 'FEE2E2',
        'excused' => 'E0E7FF',
        'day_off' => 'F1F5F9',
    ];

    private int $headerRow = 0;

    public function __construct(private array $report, private array $rows)
    {
    }

    public function title(): string
    {
        return Carbon::parse($this->report['date'])->format('d.m.Y');
    }

    public function array(): array
    {
        $r = $this->report;
        $s = $r['summary'];
        $date = Carbon::parse($r['date'])->format('d.m.Y');

        $out = [
            [__('Davomat') . ' — ' . $date],
            [$r['is_workday']
                ? __('Ish vaqti: :from–:to', ['from' => $r['work_start'], 'to' => $r['work_end']])
                : __('Dam olish')],
            [],
            [__('Jami xodimlar'), $s['total']],
            [__('Kelgan'), $s['present']],
            [__('Vaqtida'), $s['on_time']],
            [__('Kech qolgan'), $s['late']],
            [$r['day_over'] ? __('Kelmagan') : __('Hali yetib kelmagan'), $s['absent'] + $s['not_arrived']],
            [__('Sababli (botda yozgan)'), $s['excused']],
            [__('Erta ketgan'), $s['early_left']],
            [],
        ];

        if ($r['is_today'] && !$r['day_over']) {
            array_splice($out, 2, 0, [[__('Holat :time dagi', ['time' => $r['generated_at']])]]);
        }

        $out[] = [
            '#', __('Xodim'), 'ID', __('Bo‘lim'), __('Holat'), __('Keldi'),
            __('Kechikish (daq)'), __('Ketdi'), __('Erta ketish (daq)'), __('Kechikish sababi'), __('Kela olmaslik sababi'),
        ];
        $this->headerRow = count($out);

        foreach ($this->rows as $i => $row) {
            $lateReason = '';
            if ($row['status'] === 'late') {
                $lateReason = $row['late_reason']
                    ?: ($row['reason_required'] ? __('Sabab hali yozilmagan') : __('Sabab so‘ralmaydi (:n daq gacha)', ['n' => $r['late_grace_minutes']]));
            }

            $out[] = [
                $i + 1,
                $row['name'],
                $row['person_id'],
                $row['department'],
                $this->statusLabel($row['status']),
                $row['arrived_at'] ?? '',
                $row['late_minutes'] ?: null,
                $row['left_at'] ?? '',
                $row['early_minutes'] ?: null,
                $lateReason,
                $row['excuse_reason'] ?? '',
            ];
        }

        return $out;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            $this->headerRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $last = $this->headerRow + count($this->rows);

                $sheet->freezePane('A' . ($this->headerRow + 1));
                if ($this->rows) {
                    $sheet->setAutoFilter("A{$this->headerRow}:K{$last}");
                }

                // Holat ustunini rang bilan ajratamiz
                foreach ($this->rows as $i => $row) {
                    $color = self::STATUS_COLORS[$row['status']] ?? null;
                    if ($color) {
                        $sheet->getStyle('E' . ($this->headerRow + 1 + $i))->getFill()
                            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
                    }
                }

                // Uzun sabablar: kenglikni cheklab, matnni o'raymiz
                foreach (['J', 'K'] as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(false)->setWidth(45);
                    $sheet->getStyle("{$col}{$this->headerRow}:{$col}{$last}")->getAlignment()->setWrapText(true);
                }
            },
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'on_time' => __('Vaqtida'),
            'late' => __('Kech qoldi'),
            'not_arrived' => __('Hali kelmagan'),
            'absent' => __('Kelmagan'),
            'excused' => __('Sababli'),
            'day_off' => __('Dam olish'),
            default => $status,
        };
    }
}
