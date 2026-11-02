<?php

namespace App\Services;

use App\Models\HhResume;
use App\Models\HhSearch;
use App\Models\HhSearchResume;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Saqlangan filtr bo'yicha hh dan yangi rezyumelarni olib, bazaga yozadi va foizini hisoblaydi.
 * Qidiruv (GET /resumes) API limitidan yemaydi — to'liq rezyume ochilmaydi.
 */
class HhFetcher
{
    /** hh qidiruvining maksimal chuqurligi 2000 (20 sahifa × 100) */
    private const PER_PAGE = 100;
    private const MAX_PAGES = 20;

    /** Birinchi ishga tushirishda shuncha kunlik rezyumelar olinadi */
    private const FIRST_RUN_DAYS = 30;

    public function __construct(private HhService $hh, private HhTelegramBot $bot)
    {
    }

    /**
     * @return array{found: int, fetched: int, new: int, notified: int}
     */
    public function run(HhSearch $search): array
    {
        $startedAt = now();
        // Birinchi ishga tushirishda oxirgi 30 kun olinadi — ular faqat panelga, botga emas
        $firstRun = $search->last_run_at === null;
        // Oldingi ishga tushirish bilan 1 soat ustma-ust — chegaradagi rezyumelar tushib qolmasin
        $from = $search->last_run_at
            ? $search->last_run_at->copy()->subHour()
            : now()->subDays(self::FIRST_RUN_DAYS);

        $scorer = new HhResumeScorer($this->hh->currencyRates());
        $params = $search->params ?? [];
        $query = $search->hhQuery() + [
            'order_by' => 'publication_time',
            'per_page' => self::PER_PAGE,
            'date_from' => $from->format('Y-m-d\TH:i:sO'),
            'with_job_search_status' => 'true',
        ];

        $found = 0;
        $fetched = 0;
        $newIds = [];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $result = $this->hh->searchResumes($query + ['page' => $page]);
            $found = (int) ($result['found'] ?? 0);

            foreach ($result['items'] ?? [] as $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $fetched++;
                if ($id = $this->store($search, $params, $item, $scorer)) {
                    $newIds[] = $id;
                }
            }

            if ($page + 1 >= (int) ($result['pages'] ?? 0)) {
                break;
            }
        }

        $search->forceFill([
            'last_run_at' => $startedAt,
            'last_found' => $found,
            'last_error' => null,
        ])->save();

        $notified = 0;
        if (!$firstRun) {
            try {
                $notified = $this->bot->notify($search, $newIds);
            } catch (\Throwable $e) {
                MonitorNotifier::send("HH bot [#{$search->id} {$search->name}] yuborishda xato: " . $e->getMessage());
            }
        }

        return ['found' => $found, 'fetched' => $fetched, 'new' => count($newIds), 'notified' => $notified];
    }

    /** @return int|null yangi qo'shilgan bo'lsa hh_search_resumes.id */
    private function store(HhSearch $search, array $params, array $item, HhResumeScorer $scorer): ?int
    {
        $salary = $item['salary'] ?? null;
        $amount = isset($salary['amount']) ? (float) $salary['amount'] : null;

        return DB::transaction(function () use ($search, $params, $item, $scorer, $salary, $amount) {
            $resume = HhResume::updateOrCreate(['resume_id' => $item['id']], [
                'title' => mb_substr((string) ($item['title'] ?? ''), 0, 255),
                'age' => $item['age'] ?? null,
                'gender' => $item['gender']['id'] ?? null,
                'area_id' => $item['area']['id'] ?? null,
                'area_name' => $item['area']['name'] ?? null,
                'salary_amount' => $amount,
                'salary_currency' => $salary['currency'] ?? null,
                'salary_uzs' => $scorer->toUzs($amount, $salary['currency'] ?? null),
                'experience_months' => $item['total_experience']['months'] ?? null,
                'education_level' => $item['education']['level']['id'] ?? null,
                'hh_updated_at' => isset($item['updated_at']) ? Carbon::parse($item['updated_at']) : null,
                'alternate_url' => $this->hhUzUrl($item['alternate_url'] ?? null),
                'data' => $item,
            ]);

            [$score, $details] = $scorer->score($params, $item);

            $link = HhSearchResume::firstOrNew(['hh_search_id' => $search->id, 'hh_resume_id' => $resume->id]);
            $isNew = !$link->exists;
            $link->fill(['score' => $score, 'score_details' => $details]);
            $link->save();

            return $isNew ? $link->id : null;
        });
    }

    /** HR hh.uz akkauntida ishlaydi — havolani hh.uz ga yo'naltiramiz */
    private function hhUzUrl(?string $url): ?string
    {
        return $url ? preg_replace('#^https?://(www\.)?hh\.ru/#', 'https://hh.uz/', $url) : null;
    }
}
