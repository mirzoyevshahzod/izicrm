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

    public function __construct(private HhService $hh)
    {
    }

    /**
     * @return array{found: int, fetched: int, new: int}
     */
    public function run(HhSearch $search): array
    {
        $startedAt = now();
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
        $new = 0;

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $result = $this->hh->searchResumes($query + ['page' => $page]);
            $found = (int) ($result['found'] ?? 0);

            foreach ($result['items'] ?? [] as $item) {
                if (empty($item['id'])) {
                    continue;
                }
                $fetched++;
                $new += $this->store($search, $params, $item, $scorer) ? 1 : 0;
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

        return compact('found', 'fetched', 'new');
    }

    /** @return bool yangi qo'shildimi */
    private function store(HhSearch $search, array $params, array $item, HhResumeScorer $scorer): bool
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

            return $isNew;
        });
    }

    /** HR hh.uz akkauntida ishlaydi — havolani hh.uz ga yo'naltiramiz */
    private function hhUzUrl(?string $url): ?string
    {
        return $url ? preg_replace('#^https?://(www\.)?hh\.ru/#', 'https://hh.uz/', $url) : null;
    }
}
