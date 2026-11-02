<?php

namespace App\Services;

/**
 * Rezyumening filtrga moslik foizi (0–100) va "nega" izohi.
 *
 * hh qidiruvi allaqachon filtrga mos rezyumelarni qaytaradi, shuning uchun bu ball
 * ularni bir-biridan ajratish uchun: kalit so'zlar sarlavhada va oldingi lavozimlarda
 * qanchalik bor, tajriba / maosh / yosh va boshqalar qanchalik to'g'ri keladi.
 * Faqat filtrda ko'rsatilgan mezonlar hisobga olinadi (og'irliklar shularga bo'linadi).
 * Izohlar kod + qiymat ko'rinishida saqlanadi (note, args), matnni panel o'z tilida yasaydi.
 */
class HhResumeScorer
{
    private const EXPERIENCE_RANGES = [
        'noExperience' => [0, 11],
        'between1And3' => [12, 36],
        'between3And6' => [36, 72],
        'moreThan6' => [72, PHP_INT_MAX],
    ];

    private const EDUCATION_RANK = [
        'secondary' => 1, 'special_secondary' => 2, 'unfinished_higher' => 3,
        'higher' => 4, 'bachelor' => 4, 'master' => 5, 'candidate' => 6, 'doctor' => 7,
    ];

    /** @param array $rates hh valyuta kurslari (RUR ga nisbatan) */
    public function __construct(private array $rates = [])
    {
    }

    /**
     * @return array{0: int, 1: array<int, array{key: string, weight: int, score: float, note: string, args: array}>}
     */
    public function score(array $params, array $item): array
    {
        $details = [];
        $add = function (string $key, int $weight, float $score, string $note, array $args = []) use (&$details) {
            $details[] = ['key' => $key, 'weight' => $weight, 'score' => round(max(0, min(1, $score)), 2), 'note' => $note, 'args' => $args];
        };

        $terms = $this->terms($params['keywords'] ?? []);
        if ($terms) {
            $title = $this->normalize($item['title'] ?? '');
            $hits = array_filter($terms, fn ($t) => $this->contains($title, $t));
            $add('title', 35, count($hits) / count($terms),
                $hits ? 'title_hits' : 'title_none', ['terms' => implode(', ', $hits)]);

            $jobs = array_slice($item['experience'] ?? [], 0, 5);
            $positions = $this->normalize(implode(' | ', array_map(
                fn ($j) => ($j['position'] ?? '') . ' ' . ($j['company'] ?? ''), $jobs
            )));
            $recent = $this->normalize(($jobs[0]['position'] ?? '') . ' ' . ($jobs[0]['company'] ?? ''));
            $posHits = array_filter($terms, fn ($t) => $this->contains($positions, $t));
            $recentHit = (bool) array_filter($terms, fn ($t) => $this->contains($recent, $t));
            $add('positions', 20,
                ($posHits ? count($posHits) / count($terms) * 0.7 : 0) + ($recentHit ? 0.3 : 0),
                $posHits ? ($recentHit ? 'pos_recent' : 'pos_earlier') : 'pos_none');
        }

        $months = $item['total_experience']['months'] ?? null;
        if (!empty($params['experience'])) {
            if ($months === null) {
                $add('experience', 15, 0.5, 'unknown');
            } else {
                $add('experience', 15, $this->experienceScore((int) $months, $params['experience']), 'months', ['months' => (int) $months]);
            }
        }

        if (isset($params['salary_from']) || isset($params['salary_to'])) {
            $add('salary', 10, ...$this->salaryScore($params, $item['salary'] ?? null));
        }

        if (isset($params['age_from']) || isset($params['age_to'])) {
            $age = $item['age'] ?? null;
            if ($age === null) {
                $add('age', 5, 0.5, 'unknown');
            } else {
                $in = (!isset($params['age_from']) || $age >= $params['age_from']) && (!isset($params['age_to']) || $age <= $params['age_to']);
                $add('age', 5, $in ? 1 : 0.2, 'age', ['age' => $age]);
            }
        }

        if (!empty($params['education_level'])) {
            $level = $item['education']['level']['id'] ?? null;
            if ($level === null) {
                $add('education', 5, 0.5, 'unknown');
            } else {
                $min = min(array_map(fn ($l) => self::EDUCATION_RANK[$l] ?? 0, $params['education_level']));
                $add('education', 5, in_array($level, $params['education_level'], true) || (self::EDUCATION_RANK[$level] ?? 0) >= $min ? 1 : 0.3,
                    'education', ['level' => $level]);
            }
        }

        if (!empty($params['area'])) {
            $areaId = (string) ($item['area']['id'] ?? '');
            $inArea = in_array($areaId, $params['area'], true);
            $add('area', 5, $inArea ? 1 : 0.5, $inArea ? 'area' : 'area_relocate', ['area_id' => $areaId, 'name' => $item['area']['name'] ?? '—']);
        }

        $updated = isset($item['updated_at']) ? strtotime($item['updated_at']) : null;
        $days = $updated ? (int) floor((time() - $updated) / 86400) : null;
        $add('fresh', 5,
            $days === null ? 0.3 : ($days <= 7 ? 1 : ($days <= 30 ? 0.6 : 0.3)),
            $days === null ? 'unknown' : 'days', ['days' => $days]);

        $total = array_sum(array_column($details, 'weight'));
        $score = $total ? (int) round(array_sum(array_map(fn ($d) => $d['weight'] * $d['score'], $details)) / $total * 100) : 0;

        return [$score, $details];
    }

    /** Maoshni so'mga o'giradi (hh kurslari RUR ga nisbatan) */
    public function toUzs(?float $amount, ?string $currency): ?float
    {
        if ($amount === null || !$currency) {
            return null;
        }
        $from = $this->rates[$currency] ?? null;
        $uzs = $this->rates['UZS'] ?? null;

        return $from && $uzs ? round($amount / $from * $uzs, 2) : null;
    }

    private function salaryScore(array $params, ?array $salary): array
    {
        if (empty($salary['amount'])) {
            return [0.5, 'unknown'];
        }
        $currency = $params['currency'] ?? 'UZS';
        $wanted = $currency === ($salary['currency'] ?? null)
            ? (float) $salary['amount']
            : $this->convert((float) $salary['amount'], $salary['currency'] ?? '', $currency);
        $args = ['amount' => (float) $salary['amount'], 'currency' => $salary['currency'] ?? ''];
        if ($wanted === null) {
            return [0.5, 'salary', $args];
        }

        $to = $params['salary_to'] ?? null;
        $from = $params['salary_from'] ?? null;
        if ($to && $wanted > $to) {
            return [$wanted <= $to * 1.2 ? 0.5 : 0.1, 'salary_over', $args];
        }
        if ($from && $wanted < $from * 0.7) {
            return [0.6, 'salary_low', $args];
        }

        return [1, 'salary', $args];
    }

    private function convert(float $amount, string $from, string $to): ?float
    {
        $a = $this->rates[$from] ?? null;
        $b = $this->rates[$to] ?? null;

        return $a && $b ? $amount / $a * $b : null;
    }

    private function experienceScore(int $months, array $wanted): float
    {
        $best = 0;
        foreach ($wanted as $key) {
            [$min, $max] = self::EXPERIENCE_RANGES[$key] ?? [0, PHP_INT_MAX];
            if ($months >= $min && $months <= $max) {
                return 1;
            }
            $gap = $months < $min ? $min - $months : $months - $max;
            $best = max($best, $gap <= 12 ? 0.6 : ($gap <= 24 ? 0.3 : 0));
        }

        return $best;
    }

    /** Kalit so'zlardan qidiriladigan so'zlar ("istisno" mantig'idagilar tashlanadi) */
    private function terms(array $keywords): array
    {
        $terms = [];
        foreach ($keywords as $k) {
            if (($k['logic'] ?? 'all') === 'except') {
                continue;
            }
            $text = $this->normalize($k['text'] ?? '');
            if (($k['logic'] ?? '') === 'phrase') {
                $terms[] = $text;
                continue;
            }
            foreach (preg_split('/[\s,;\/|]+/u', $text) as $word) {
                $word = trim($word, '"\'()!*');
                if (mb_strlen($word) >= 3 && !in_array($word, ['and', 'not', 'для', 'или'], true)) {
                    $terms[] = $word;
                }
            }
        }

        return array_values(array_unique(array_filter($terms)));
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(str_replace(['ё', 'ʻ', 'ʼ', '‘', '’', '`'], ['е', "'", "'", "'", "'", "'"], $text));
    }

    /** So'z qo'shimchalariga chidamli moslik: "менеджер" ↔ "менеджером", "logist" ↔ "logistika" */
    private function contains(string $haystack, string $term): bool
    {
        if ($haystack === '' || $term === '') {
            return false;
        }
        if (str_contains($term, ' ')) {
            return str_contains($haystack, $term);
        }
        $len = mb_strlen($term);
        $stem = $len > 5 ? mb_substr($term, 0, $len - 2) : $term;

        return str_contains($haystack, $stem);
    }
}
