<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * HR saqlagan qidiruv filtri. params — panel formasidagi qiymatlar,
 * hhQuery() ularni GET /resumes parametrlariga aylantiradi.
 */
class HhSearch extends Model
{
    /** Hudud tanlanmasa — butun O'zbekiston (hh areas: 97) */
    public const UZBEKISTAN_AREA = '97';

    /** hh /dictionaries dagi ruxsat etilgan qiymatlar */
    private const ALLOWED = [
        'field' => ['everywhere', 'title', 'education', 'skills', 'experience', 'experience_company', 'experience_position', 'experience_description'],
        'logic' => ['all', 'any', 'phrase', 'except'],
        'period' => ['all_time', 'last_year', 'last_three_years', 'last_six_years'],
        'relocation' => ['living_or_relocation', 'living', 'living_but_relocation', 'relocation'],
        'experience' => ['noExperience', 'between1And3', 'between3And6', 'moreThan6'],
        'education_level' => ['secondary', 'special_secondary', 'unfinished_higher', 'higher', 'bachelor', 'master', 'candidate', 'doctor'],
        'gender' => ['male', 'female'],
        'language_level' => ['a1', 'a2', 'b1', 'b2', 'c1', 'c2', 'l1'],
        'employment_form' => ['FULL', 'PART_TIME', 'INTERNSHIP', 'VOLUNTEER'],
        'work_format' => ['ON_SITE', 'REMOTE', 'HYBRID', 'FIELD_WORK', 'FLY_IN_FLY_OUT'],
        'driver_license_types' => ['A', 'B', 'C', 'D', 'E', 'BE', 'CE', 'DE', 'TM', 'TB'],
        'job_search_status' => ['active_search', 'looking_for_offers', 'not_looking_for_job', 'has_job_offer', 'accepted_job_offer'],
        'label' => ['only_with_photo', 'only_with_salary', 'only_with_age', 'only_with_gender', 'only_with_vehicle', 'exclude_viewed_by_user_id', 'exclude_viewed_by_employer_id'],
        'currency' => ['UZS', 'USD', 'RUR', 'EUR', 'KZT', 'KGS'],
    ];

    protected $table = 'hh_searches';

    protected $fillable = [
        'name',
        'params',
        'is_active',
        'notify',
        'notify_min_score',
        'created_by',
    ];

    protected $casts = [
        'params' => 'array',
        'is_active' => 'boolean',
        'notify' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function results(): HasMany
    {
        return $this->hasMany(HhSearchResume::class);
    }

    /**
     * Formadan kelgan qiymatlarni tozalaydi: faqat ma'lum kalitlar va hh qabul qiladigan qiymatlar qoladi.
     */
    public static function normalizeParams(array $in): array
    {
        $list = function ($value, ?array $allowed = null, int $max = 50) {
            $items = array_map(fn ($v) => trim((string) $v), is_array($value) ? $value : []);
            $items = array_filter($items, fn ($v) => $v !== '' && ($allowed === null || in_array($v, $allowed, true)));
            return array_slice(array_values(array_unique($items)), 0, $max);
        };
        $ids = fn ($value, int $max = 50) => array_values(array_filter($list($value, null, $max), 'ctype_digit'));
        $int = fn ($value, int $min, int $max) => is_numeric($value) && $value >= $min && $value <= $max ? (int) $value : null;
        $one = fn ($value, string $key) => in_array($value, self::ALLOWED[$key], true) ? $value : null;

        $keywords = [];
        foreach (array_slice(is_array($in['keywords'] ?? null) ? $in['keywords'] : [], 0, 10) as $row) {
            $text = mb_substr(trim((string) ($row['text'] ?? '')), 0, 200);
            if ($text === '') {
                continue;
            }
            $keywords[] = [
                'text' => $text,
                'field' => $one($row['field'] ?? null, 'field') ?? 'everywhere',
                'logic' => $one($row['logic'] ?? null, 'logic') ?? 'all',
                'period' => $one($row['period'] ?? null, 'period') ?? 'all_time',
            ];
        }

        $languages = [];
        foreach (array_slice(is_array($in['language'] ?? null) ? $in['language'] : [], 0, 10) as $row) {
            $id = strtolower(trim((string) ($row['id'] ?? '')));
            $level = $one($row['level'] ?? null, 'language_level');
            if (preg_match('/^[a-z]{2,4}$/', $id) && $level) {
                $languages[] = ['id' => $id, 'level' => $level];
            }
        }

        $skills = [];
        foreach (array_slice(is_array($in['skill'] ?? null) ? $in['skill'] : [], 0, 30) as $row) {
            if (ctype_digit((string) ($row['id'] ?? ''))) {
                $skills[] = ['id' => (string) $row['id'], 'text' => mb_substr((string) ($row['text'] ?? ''), 0, 100)];
            }
        }

        $areas = $ids($in['area'] ?? []);
        $salaryFrom = $int($in['salary_from'] ?? null, 0, 10_000_000_000);
        $salaryTo = $int($in['salary_to'] ?? null, 0, 10_000_000_000);

        return [
            'keywords' => $keywords,
            'professional_role' => $ids($in['professional_role'] ?? []),
            'area' => $areas,
            'relocation' => $areas ? $one($in['relocation'] ?? null, 'relocation') : null,
            'salary_from' => $salaryFrom,
            'salary_to' => $salaryTo,
            'currency' => $one($in['currency'] ?? null, 'currency') ?? 'UZS',
            'age_from' => $int($in['age_from'] ?? null, 14, 100),
            'age_to' => $int($in['age_to'] ?? null, 14, 100),
            'gender' => $one($in['gender'] ?? null, 'gender'),
            'experience' => $list($in['experience'] ?? [], self::ALLOWED['experience']),
            'education_level' => $list($in['education_level'] ?? [], self::ALLOWED['education_level']),
            'language' => $languages,
            'skill' => $skills,
            'employment_form' => $list($in['employment_form'] ?? [], self::ALLOWED['employment_form']),
            'work_format' => $list($in['work_format'] ?? [], self::ALLOWED['work_format']),
            'driver_license_types' => $list($in['driver_license_types'] ?? [], self::ALLOWED['driver_license_types']),
            'job_search_status' => $list($in['job_search_status'] ?? [], self::ALLOWED['job_search_status']),
            'label' => $list($in['label'] ?? [], self::ALLOWED['label']),
        ];
    }

    /**
     * GET /resumes parametrlari (ro'yxatli qiymatlar hh da key=a&key=b bo'lib ketadi).
     */
    public static function toHhQuery(array $p): array
    {
        $q = [];

        // text.* — har bir kalit so'z uchun to'liq uchlik bilan (hh talabi)
        foreach ($p['keywords'] ?? [] as $k) {
            $q['text'][] = $k['text'];
            $q['text.field'][] = $k['field'];
            $q['text.logic'][] = $k['logic'];
            $q['text.period'][] = $k['period'];
        }

        $q['area'] = $p['area'] ?: [self::UZBEKISTAN_AREA];
        if (!empty($p['relocation'])) {
            $q['relocation'] = $p['relocation'];
        }

        foreach (['professional_role', 'experience', 'employment_form', 'work_format', 'driver_license_types', 'job_search_status', 'label'] as $key) {
            if (!empty($p[$key])) {
                $q[$key] = $p[$key];
            }
        }
        if (!empty($p['education_level'])) {
            $q['education_levels'] = $p['education_level'];
        }
        if (!empty($p['skill'])) {
            $q['skill'] = array_column($p['skill'], 'id');
        }
        if (!empty($p['language'])) {
            $q['language'] = array_map(fn ($l) => $l['id'] . '.' . $l['level'], $p['language']);
        }

        foreach (['salary_from', 'salary_to', 'age_from', 'age_to'] as $key) {
            if (isset($p[$key])) {
                $q[$key] = $p[$key];
            }
        }
        if (isset($p['salary_from']) || isset($p['salary_to'])) {
            $q['currency'] = $p['currency'] ?? 'UZS';
        }
        if (!empty($p['gender'])) {
            $q['gender'] = $p['gender'];
        }

        return $q;
    }

    public function hhQuery(): array
    {
        return self::toHhQuery($this->params ?? []);
    }
}
