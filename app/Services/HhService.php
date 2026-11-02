<?php

namespace App\Services;

use App\Models\HhToken;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * hh.uz API klienti (https://api.hh.ru, ?host=hh.uz).
 * Token bitta — kompaniyaning ish beruvchi akkaunti (hh_tokens jadvalidagi oxirgi yozuv).
 */
class HhService
{
    // ---- OAuth ----

    /**
     * Avtorizatsiya havolasi. state 10 daqiqa cache'da turadi, callback uni tekshiradi.
     */
    public static function authorizeUrl(): string
    {
        $state = Str::random(40);
        Cache::put("hh_oauth_state:$state", true, now()->addMinutes(10));

        return config('services.hh.oauth_url') . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => config('services.hh.client_id'),
            'redirect_uri' => config('services.hh.redirect_uri'),
            'state' => $state,
        ]);
    }

    public static function checkState(?string $state): bool
    {
        return $state !== null && Cache::pull("hh_oauth_state:$state") === true;
    }

    /**
     * authorization_code ni tokenga almashtiradi va hh_tokens ga yozadi.
     */
    public function exchangeCode(string $code): HhToken
    {
        $data = $this->tokenRequest([
            'grant_type' => 'authorization_code',
            'client_id' => config('services.hh.client_id'),
            'client_secret' => config('services.hh.client_secret'),
            'code' => $code,
            'redirect_uri' => config('services.hh.redirect_uri'),
        ]);

        $token = HhToken::create($this->tokenAttributes($data));

        $me = $this->get('/me');
        $token->update([
            'employer_id' => $me['employer']['id'] ?? null,
            'employer_name' => $me['employer']['name'] ?? null,
            'manager_id' => $me['manager']['id'] ?? $me['employer']['manager_id'] ?? null,
        ]);

        // Eski tokenlar endi kerak emas
        HhToken::where('id', '!=', $token->id)->delete();

        return $token;
    }

    public function token(): HhToken
    {
        $token = HhToken::latest('id')->first();

        if (!$token) {
            throw new RuntimeException("hh.uz ulanmagan. `php artisan hh:auth-url` bilan ulang.");
        }

        return $token;
    }

    /**
     * hh qoidasi: refresh faqat access token muddati tugagandan keyin ishlaydi,
     * refresh_token esa bir martalik. Shuning uchun lock ichida, qayta o'qib tekshiramiz.
     */
    private function refresh(): HhToken
    {
        return Cache::lock('hh_token_refresh', 30)->block(20, function () {
            $token = $this->token();

            if ($token->expires_at->isFuture()) {
                return $token; // boshqa jarayon allaqachon yangilagan
            }

            $data = $this->tokenRequest([
                'grant_type' => 'refresh_token',
                'refresh_token' => $token->refresh_token,
            ]);

            $token->update($this->tokenAttributes($data));

            return $token;
        });
    }

    private function tokenRequest(array $params): array
    {
        $response = $this->http()->asForm()->post('/token', $params);

        if ($response->failed()) {
            MonitorNotifier::send("hh.uz: token olinmadi ({$response->status()}): " . $response->body());
            throw new RuntimeException("hh token xatosi: " . $response->body());
        }

        return $response->json();
    }

    private function tokenAttributes(array $data): array
    {
        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            // 1 daqiqa oldinroq tugagan deb hisoblaymiz
            'expires_at' => now()->addSeconds(($data['expires_in'] ?? 0) - 60),
        ];
    }

    // ---- API so'rovlari ----

    public function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, $query)->json() ?? [];
    }

    public function request(string $method, string $path, array $data = []): Response
    {
        $token = $this->token();
        if ($token->expires_at->isPast()) {
            $token = $this->refresh();
        }

        $response = $this->send($token, $method, $path, $data);

        // Tekshiruv va so'rov oralig'ida muddat tugab qolgan bo'lishi mumkin
        if ($response->status() === 403 && $this->hasError($response, 'oauth', 'token_expired')) {
            $token->update(['expires_at' => now()->subSecond()]);
            $response = $this->send($this->refresh(), $method, $path, $data);
        }

        if ($response->failed()) {
            throw new RuntimeException("hh API $method $path xatosi ({$response->status()}): " . $response->body());
        }

        return $response;
    }

    private function send(HhToken $token, string $method, string $path, array $data): Response
    {
        $host = ['host' => config('services.hh.host')];
        $http = $this->http()->withToken($token->access_token);

        return $method === 'get'
            ? $http->get($path . '?' . self::buildQuery($data + $host))
            : $http->withQueryParameters($host)->{$method}($path, $data);
    }

    /**
     * hh ro'yxatli parametrlarni key=a&key=b ko'rinishida kutadi (key[]= emas).
     */
    public static function buildQuery(array $params): string
    {
        $pairs = [];
        foreach ($params as $key => $value) {
            foreach ((array) $value as $item) {
                if ($item === null || $item === '') {
                    continue;
                }
                if (is_bool($item)) {
                    $item = $item ? 'true' : 'false';
                }
                $pairs[] = rawurlencode($key) . '=' . rawurlencode((string) $item);
            }
        }

        return implode('&', $pairs);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('services.hh.api_url'))
            ->withHeaders(['HH-User-Agent' => config('services.hh.user_agent')])
            ->acceptJson()
            ->timeout(20);
    }

    private function hasError(Response $response, string $type, string $value): bool
    {
        foreach ($response->json('errors') ?? [] as $error) {
            if (($error['type'] ?? null) === $type && ($error['value'] ?? null) === $value) {
                return true;
            }
        }

        return false;
    }

    // ---- Qulay metodlar ----

    public function me(): array
    {
        return $this->get('/me');
    }

    /** Faol API xizmatlari (tarif turi va qolgan so'rovlar soni) */
    public function apiServices(): array
    {
        return $this->get("/employers/{$this->token()->employer_id}/services/payable_api_actions/active");
    }

    /** Pullik metodlar guruhlariga kirish huquqi */
    public function methodAccess(): array
    {
        $token = $this->token();

        return $this->get("/employers/{$token->employer_id}/managers/{$token->manager_id}/method_access");
    }

    public function savedSearches(): array
    {
        return $this->get('/saved_searches/resumes', ['per_page' => 10]);
    }

    public function searchResumes(array $params): array
    {
        return $this->get('/resumes', $params);
    }

    // ---- Ochiq spravochniklar (token shart emas), o'zbek tilida, 1 kun keshda ----

    public function publicGet(string $path, array $query = []): array
    {
        $query += ['host' => config('services.hh.host'), 'locale' => 'UZ'];
        $response = $this->http()->get($path . '?' . self::buildQuery($query));

        if ($response->failed()) {
            throw new RuntimeException("hh API GET $path xatosi ({$response->status()})");
        }

        return $response->json() ?? [];
    }

    public function dictionaries(): array
    {
        return Cache::remember('hh:dict:dictionaries', now()->addDay(), fn () => $this->publicGet('/dictionaries'));
    }

    /** O'zbekiston shahar va tumanlari: [['id' => '2759', 'name' => 'Toshkent'], ...] */
    public function uzbekistanAreas(): array
    {
        return Cache::remember('hh:dict:areas_uz', now()->addDay(), function () {
            $root = $this->publicGet('/areas/' . \App\Models\HhSearch::UZBEKISTAN_AREA);
            $flat = [];
            $walk = function (array $areas) use (&$walk, &$flat) {
                foreach ($areas as $area) {
                    $flat[] = ['id' => (string) $area['id'], 'name' => $area['name']];
                    $walk($area['areas'] ?? []);
                }
            };
            $walk($root['areas'] ?? []);
            usort($flat, fn ($a, $b) => strcmp($a['name'], $b['name']));

            return $flat;
        });
    }

    public function professionalRoles(): array
    {
        return Cache::remember('hh:dict:roles', now()->addDay(), function () {
            return array_map(fn ($c) => [
                'name' => $c['name'],
                'roles' => array_map(fn ($r) => ['id' => (string) $r['id'], 'name' => $r['name']], $c['roles']),
            ], $this->publicGet('/professional_roles')['categories'] ?? []);
        });
    }

    public function languages(): array
    {
        return Cache::remember('hh:dict:languages', now()->addDay(), fn () => array_map(
            fn ($l) => ['id' => $l['id'], 'name' => $l['name']],
            $this->publicGet('/languages')
        ));
    }

    public function suggestSkills(string $text): array
    {
        $text = mb_substr(trim($text), 0, 50);
        if (mb_strlen($text) < 2) {
            return [];
        }

        return Cache::remember('hh:skill:' . md5(mb_strtolower($text)), now()->addDay(), fn () => array_map(
            fn ($i) => ['id' => (string) $i['id'], 'text' => $i['text']],
            $this->publicGet('/suggests/skill_set', ['text' => $text])['items'] ?? []
        ));
    }

    /** Valyuta kurslari (hh: RUR ga nisbatan) — maoshni so'mga o'girish uchun */
    public function currencyRates(): array
    {
        return collect($this->dictionaries()['currency'] ?? [])->pluck('rate', 'code')->all();
    }

    /** API ulanganmi (token bormi) — panelda ogohlantirish uchun */
    public static function isConnected(): bool
    {
        return HhToken::exists();
    }
}
