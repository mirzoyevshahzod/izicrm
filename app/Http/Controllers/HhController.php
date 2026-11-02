<?php

namespace App\Http\Controllers;

use App\Models\HhSearch;
use App\Models\HhSearchResume;
use App\Services\HhFetcher;
use App\Services\HhService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel: hh.uz nomzodlar bo'limi (permission: hh.view)
 */
class HhController extends Controller
{
    public function __construct(private HhService $hh)
    {
    }

    /** Filtr formasi uchun spravochniklar (o'zbek tilida, hh.uz dagi bilan bir xil) */
    public function dictionaries()
    {
        $d = $this->hh->dictionaries();
        $pick = fn (string $key) => array_map(fn ($i) => ['id' => $i['id'], 'name' => $i['name'] ?? $i['id']], $d[$key] ?? []);

        return response()->json([
            'connected' => HhService::isConnected(),
            'experience' => $pick('experience'),
            'education_level' => $pick('education_level'),
            'gender' => $pick('gender'),
            'logic' => $pick('resume_search_logic'),
            'field' => $pick('resume_search_fields'),
            'period' => $pick('resume_search_experience_period'),
            'relocation' => $pick('resume_search_relocation'),
            'label' => array_values(array_filter($pick('resume_search_label'), fn ($l) => $l['id'] !== 'only_in_responses')),
            'employment_form' => $pick('resume_employment_form'),
            'work_format' => $pick('resume_work_format'),
            'job_search_status' => $pick('job_search_statuses_employer'),
            'driver_license_types' => $pick('driver_license_types'),
            'language_level' => $pick('language_level'),
            'currency' => collect($d['currency'] ?? [])
                ->whereIn('code', ['UZS', 'USD', 'RUR', 'EUR', 'KZT'])
                ->map(fn ($c) => ['id' => $c['code'], 'name' => $c['abbr'] ?? $c['code']])->values(),
            'areas' => $this->hh->uzbekistanAreas(),
            'roles' => $this->hh->professionalRoles(),
            'languages' => $this->hh->languages(),
        ]);
    }

    public function skills(Request $request)
    {
        return response()->json($this->hh->suggestSkills((string) $request->query('text', '')));
    }

    /** Filtr bo'yicha hh da hozir nechta rezyume bor (formada jonli ko'rsatiladi) */
    public function preview(Request $request)
    {
        $params = HhSearch::normalizeParams((array) $request->input('params', []));
        $result = $this->hh->searchResumes(HhSearch::toHhQuery($params) + ['per_page' => 1]);

        return response()->json(['found' => (int) ($result['found'] ?? 0)]);
    }

    public function index()
    {
        $searches = HhSearch::query()
            ->withCount([
                'results',
                'results as new_count' => fn ($q) => $q->where('status', 'new'),
                'results as selected_count' => fn ($q) => $q->where('status', 'selected'),
            ])
            ->latest()
            ->get();

        return response()->json([
            'connected' => HhService::isConnected(),
            'searches' => $searches,
        ]);
    }

    public function show(HhSearch $search)
    {
        return response()->json($search);
    }

    public function store(Request $request)
    {
        $search = HhSearch::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return response()->json($search, 201);
    }

    public function update(Request $request, HhSearch $search)
    {
        $data = $this->validated($request);
        // Filtr o'zgarsa, keyingi ishga tushirishda oxirgi 30 kun qaytadan ko'riladi
        if ($data['params'] != $search->params) {
            $search->last_run_at = null;
        }
        $search->fill($data)->save();

        return response()->json($search);
    }

    public function destroy(HhSearch $search)
    {
        $search->delete();

        return response()->json(['message' => 'Qidiruv o‘chirildi.']);
    }

    /** Qo'lda ishga tushirish (odatda har soatda o'zi ishlaydi) */
    public function run(HhSearch $search, HhFetcher $fetcher)
    {
        set_time_limit(180);

        try {
            $result = $fetcher->run($search);
        } catch (\Throwable $e) {
            $search->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 1000)])->save();

            return response()->json(['message' => 'hh.uz dan olib bo‘lmadi: ' . $e->getMessage()], 502);
        }

        return response()->json($result);
    }

    public function candidates(Request $request, HhSearch $search)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(HhSearchResume::STATUSES)],
            'min_score' => 'nullable|integer|min:0|max:100',
            'sort' => 'nullable|in:score,date',
        ]);

        $query = $search->results()
            ->with('resume')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->min_score, fn ($q, $m) => $q->where('score', '>=', $m));

        $request->sort === 'date'
            ? $query->join('hh_resumes', 'hh_resumes.id', '=', 'hh_search_resumes.hh_resume_id')
                ->orderByDesc('hh_resumes.hh_updated_at')->select('hh_search_resumes.*')
            : $query->orderByDesc('score')->orderByDesc('id');

        $page = $query->paginate(20);

        return response()->json([
            'search' => $search,
            'counts' => $search->results()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'data' => collect($page->items())->map(fn (HhSearchResume $r) => $this->candidatePayload($r)),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function updateCandidate(Request $request, HhSearchResume $candidate)
    {
        $data = $request->validate(['status' => ['required', Rule::in(HhSearchResume::STATUSES)]]);
        $candidate->update($data);

        return response()->json(['status' => $candidate->status]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'params' => 'required|array',
        ], [
            'name.required' => 'Qidiruvga nom bering.',
        ]);
        $data['params'] = HhSearch::normalizeParams($data['params']);

        return $data;
    }

    private function candidatePayload(HhSearchResume $r): array
    {
        $resume = $r->resume;
        $d = $resume->data ?? [];

        return [
            'id' => $r->id,
            'score' => $r->score,
            'details' => $r->score_details,
            'status' => $r->status,
            'found_at' => $r->created_at,
            'resume' => [
                'title' => $resume->title,
                'age' => $resume->age,
                'gender' => $d['gender']['name'] ?? null,
                'area' => $resume->area_name,
                'salary' => $resume->salary_amount ? ['amount' => $resume->salary_amount, 'currency' => $resume->salary_currency] : null,
                'experience_months' => $resume->experience_months,
                'jobs' => array_map(fn ($j) => [
                    'position' => $j['position'] ?? null,
                    'company' => $j['company'] ?? ($j['employer']['name'] ?? null),
                    'start' => $j['start'] ?? null,
                    'end' => $j['end'] ?? null,
                ], array_slice($d['experience'] ?? [], 0, 3)),
                'education' => [
                    'level' => $d['education']['level']['name'] ?? null,
                    'place' => $d['education']['primary'][0]['name'] ?? null,
                    'result' => $d['education']['primary'][0]['result'] ?? null,
                    'year' => $d['education']['primary'][0]['year'] ?? null,
                ],
                'job_search_status' => $d['job_search_status']['name'] ?? null,
                'updated_at' => $resume->hh_updated_at,
                'url' => $resume->alternate_url,
            ],
        ];
    }
}
