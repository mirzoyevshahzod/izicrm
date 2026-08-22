<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use PhpOffice\PhpWord\TemplateProcessor;

class EGSAttendanceBotController extends Controller
{
    protected $botToken;
    protected $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.egs_attendance_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function webhook(Request $request)
    {
        $update = $request->all();

        // ✅ Callback tugmalar (masalan "Sababini yozish")
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        $message = $update['message'] ?? null;

        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text   = $message['text'] ?? null;

        if (!$chatId) {
            return response()->json(['ok' => true]);
        }

        // ✅ Allaqachon ro'yxatdan o'tganmi?
        $existingEmployee = DB::table('attendance_employees')
            ->where('chat_id', $chatId)
            ->first();

        // 🔵 SABAB KUTILMOQDA — bu eng yuqori ustuvorlik, chunki xodim javob yozayotgan bo'lishi mumkin
        $lateEvent = DB::table('attendance_late_events')
            ->where('chat_id', $chatId)
            ->where('status', 'waiting_reason')
            ->latest()
            ->first();

        if ($lateEvent && $text) {
            $this->handleLateReasonInput($chatId, $lateEvent, $text, $existingEmployee);
            return response()->json(['ok' => true]);
        }

        // 🔵 REGISTER FLOW — telefon kutilmoqda
        $registerState = cache()->get("egs_register_state_{$chatId}");

        if ($registerState === 'waiting_phone') {

            if (isset($message['contact']['phone_number'])) {
                $phone = $message['contact']['phone_number'];

                cache()->put("egs_register_phone_{$chatId}", $phone, 600);
                cache()->put("egs_register_state_{$chatId}", 'waiting_fio', 600);

                $this->sendMessage(
                    $chatId,
                    "✅ Raqam qabul qilindi.\n\n📝 Endi ism va familiyangizni kiriting.\n\nMasalan: Firdavs Raxmatov"
                );
            } else {
                $this->sendMessageWithContactButton(
                    $chatId,
                    "❗️ Iltimos, tugma orqali telefon raqamingizni yuboring."
                );
            }

            return response()->json(['ok' => true]);
        }

        // 🔵 REGISTER FLOW — FIO kutilmoqda
        if ($registerState === 'waiting_fio' && $text) {
            $this->handleFioInput($chatId, $text);
            return response()->json(['ok' => true]);
        }

        // ▶️ /start
        if ($text === '/start') {

            if ($existingEmployee) {
                $this->sendMessage(
                    $chatId,
                    "👋 Assalomu alaykum, {$existingEmployee->first_name}!\n\nSiz allaqachon ro'yxatdan o'tgansiz. ✅"
                );
                return response()->json(['ok' => true]);
            }

            cache()->put("egs_register_state_{$chatId}", 'waiting_phone', 600);

            $this->sendMessageWithContactButton(
                $chatId,
                "👋 Assalomu alaykum!\n\nDavomat botidan foydalanish uchun ro'yxatdan o'tishingiz kerak.\n\n📱 Iltimos, telefon raqamingizni yuboring:"
            );

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }
    /**
     * FIO kiritilganda — bazadan qidirib, topilsa chat_id va phone yozadi
     */
    private function handleFioInput(int $chatId, string $text): void
    {
        $normalizedInput = $this->normalizeName($text);

        if (empty($normalizedInput)) {
            $this->sendMessage($chatId, "❌ Iltimos, ism va familiyangizni to'g'ri kiriting.\n\nMasalan: Firdavs Raxmatov");
            return;
        }

        // Hali biriktirilmagan xodimlarni olamiz (kichik jadval — PHP darajasida solishtiramiz)
        $candidates = DB::table('attendance_employees')
            ->whereNull('chat_id')
            ->get(['id', 'person_id', 'first_name', 'last_name']);

        $matched = null;

        foreach ($candidates as $employee) {
            $normalizedDbName = $this->normalizeName($employee->first_name . ' ' . $employee->last_name);
            $normalizedDbNameReversed = $this->normalizeName($employee->last_name . ' ' . $employee->first_name);

            if ($normalizedInput === $normalizedDbName || $normalizedInput === $normalizedDbNameReversed) {
                $matched = $employee;
                break;
            }
        }

        if (!$matched) {
            $this->sendMessage(
                $chatId,
                "❌ Sizning ism-familiyangiz ro'yxatda topilmadi.\n\n"
                ."Iltimos, to'g'ri yozganingizga ishonch hosil qiling (masalan: Firdavs Raxmatov), "
                ."yoki HR bilan bog'laning."
            );
            return;
        }

        $phone = cache()->get("egs_register_phone_{$chatId}");

        DB::table('attendance_employees')
            ->where('id', $matched->id)
            ->update([
                'chat_id'    => $chatId,
                'phone'      => $phone,
                'updated_at' => now(),
            ]);

        cache()->forget("egs_register_state_{$chatId}");
        cache()->forget("egs_register_phone_{$chatId}");

        $this->sendMessage(
            $chatId,
            "✅ Muvaffaqiyatli ro'yxatdan o'tdingiz!\n\n👤 {$matched->first_name} {$matched->last_name}\n\nEndi kech qolganingizda bot sizga avtomatik xabar yuboradi."
        );
    }

    /**
     * Ismni solishtirish uchun bir xil formatga keltiradi:
     * - katta harflarga o'tkazadi
     * - krill harflarni lotinga o'giradi
     * - ortiqcha bo'shliqlarni tozalaydi
     */
    private function normalizeName(string $value): string
    {
        $value = trim($value);
        $value = $this->cyrillicToLatin($value);
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[\'ʻʼ`’‘]/u', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    /**
     * O'zbekcha krill harflarni lotin harflariga o'giradi
     */
    private function cyrillicToLatin(string $text): string
    {
        $map = [
            'ў' => 'oʻ', 'Ў' => 'Oʻ',
            'қ' => 'q', 'Қ' => 'Q',
            'ғ' => 'gʻ', 'Ғ' => 'Gʻ',
            'ҳ' => 'h', 'Ҳ' => 'H',
            'а' => 'a', 'А' => 'A',
            'б' => 'b', 'Б' => 'B',
            'в' => 'v', 'В' => 'V',
            'г' => 'g', 'Г' => 'G',
            'д' => 'd', 'Д' => 'D',
            'е' => 'e', 'Е' => 'E',
            'ё' => 'yo', 'Ё' => 'Yo',
            'ж' => 'j', 'Ж' => 'J',
            'з' => 'z', 'З' => 'Z',
            'и' => 'i', 'И' => 'I',
            'й' => 'y', 'Й' => 'Y',
            'к' => 'k', 'К' => 'K',
            'л' => 'l', 'Л' => 'L',
            'м' => 'm', 'М' => 'M',
            'н' => 'n', 'Н' => 'N',
            'о' => 'o', 'О' => 'O',
            'п' => 'p', 'П' => 'P',
            'р' => 'r', 'Р' => 'R',
            'с' => 's', 'С' => 'S',
            'т' => 't', 'Т' => 'T',
            'у' => 'u', 'У' => 'U',
            'ф' => 'f', 'Ф' => 'F',
            'х' => 'x', 'Х' => 'X',
            'ц' => 'ts', 'Ц' => 'Ts',
            'ч' => 'ch', 'Ч' => 'Ch',
            'ш' => 'sh', 'Ш' => 'Sh',
            'щ' => 'sh', 'Щ' => 'Sh',
            'ъ' => '', 'Ъ' => '',
            'ы' => 'i', 'Ы' => 'I',
            'ь' => '', 'Ь' => '',
            'э' => 'e', 'Э' => 'E',
            'ю' => 'yu', 'Ю' => 'Yu',
            'я' => 'ya', 'Я' => 'Ya',
        ];

        return strtr($text, $map);
    }

    private function sendMessageWithContactButton(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id' => $chatId,
            'text'    => $text,
            'reply_markup' => [
                'keyboard' => [
                    [
                        ['text' => '📱 Raqamni yuborish', 'request_contact' => true],
                    ],
                ],
                'resize_keyboard'   => true,
                'one_time_keyboard' => true,
            ],
        ]);
    }

    /**
     * "Sababini yozish" tugmasi bosilganda
     */
    /**
     * Tugmalar bosilganda: "Sababini yozish" → kompaniya tanlash → sabab yozish
     */
    private function handleCallback(array $callback): void
    {
        $chatId = $callback['from']['id'];
        $data   = $callback['data'] ?? '';

        // 1️⃣ "Sababini yozish" bosildi — kompaniya tanlashni ko'rsatamiz
        if ($data === 'write_late_reason') {

            $lateEvent = DB::table('attendance_late_events')
                ->where('chat_id', $chatId)
                ->where('status', 'waiting_company')
                ->latest()
                ->first();

            if (!$lateEvent) {
                $this->sendMessage($chatId, "❌ Kech qolish ma'lumotlari topilmadi yoki allaqachon yuborilgan.");
                return;
            }

            $this->showCompanyList($chatId, $callback['message']['message_id']);
            return;
        }

        // 2️⃣ Kompaniya tanlandi
        if (str_starts_with($data, 'company_')) {

            $companyKey = str_replace('company_', '', $data);

            $lateEvent = DB::table('attendance_late_events')
                ->where('chat_id', $chatId)
                ->where('status', 'waiting_company')
                ->latest()
                ->first();

            if (!$lateEvent) {
                $this->sendMessage($chatId, "❌ Kech qolish ma'lumotlari topilmadi.");
                return;
            }

            DB::table('attendance_late_events')
                ->where('id', $lateEvent->id)
                ->update([
                    'company'    => $companyKey,
                    'status'     => 'waiting_reason',
                    'updated_at' => now(),
                ]);

            Http::post($this->apiUrl . '/editMessageText', [
                'chat_id'    => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text'       => "✅ Kompaniya tanlandi.\n\n✍️ Endi kech qolish sababingizni yozing:",
            ]);

            return;
        }
    }

    /**
     * Kompaniya tanlash tugmalarini ko'rsatadi
     */
    private function showCompanyList(int $chatId, int $messageId): void
    {
        $keyboard = [
            [
                ['text' => 'EGS', 'callback_data' => 'company_egs'],
                ['text' => 'Izisol', 'callback_data' => 'company_izisol'],
            ],
            [
                ['text' => 'Eastline Express', 'callback_data' => 'company_eastline'],
                ['text' => 'Incotruck', 'callback_data' => 'company_incotruck'],
            ],
            [
                ['text' => 'Transceka', 'callback_data' => 'company_transceka'],
            ],
        ];

        Http::post($this->apiUrl . '/editMessageText', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "✍️ Iltimos, kompaniyangizni tanlang:",
            'reply_markup' => [
                'inline_keyboard' => $keyboard,
            ],
        ]);
    }

    /**
     * Xodim sabab matnini yozib yuborganda
     */
    private function handleLateReasonInput(int $chatId, object $lateEvent, string $reasonText, ?object $employee): void
    {
        DB::table('attendance_late_events')
            ->where('id', $lateEvent->id)
            ->update([
                'reason'     => $reasonText,
                'status'     => 'completed',
                'updated_at' => now(),
            ]);

        // ⬇️ Endi xodim tanlagan kompaniyadan olinadi (department'dan emas)
        $companyData = $this->getCompanyData($lateEvent->company);

        $fioShort = $this->formatFioShort($lateEvent->fio);

        DB::table('attendances')->insert([
            'chat_id'      => $chatId,
            'fio'          => $fioShort,
            'company'      => $companyData['company'],
            'day'          => $lateEvent->day,
            'month'        => $lateEvent->month,
            'year'         => $lateEvent->year,
            'reason'       => $reasonText,
            'late_minutes' => $lateEvent->late_minutes,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $docPath = $this->generateWord([
            'company'      => $companyData['company'],
            'director'     => $companyData['director'],
            'fio'          => $fioShort,
            'day'          => sprintf('%02d', $lateEvent->day),
            'month'        => sprintf('%02d', $lateEvent->month),
            'year'         => $lateEvent->year,
            'late_minutes' => $this->minutesToHoursMinutes($lateEvent->late_minutes),
            'reason'       => $reasonText,
        ]);

        Http::attach('document', file_get_contents($docPath), basename($docPath))
            ->post($this->apiUrl . '/sendDocument', [
                'chat_id' => $chatId,
                'caption' => '📄 Kech qolish bo\'yicha tushuntirish xati',
            ]);

        $hrIds = config('services.telegram.egs_hr_ids', []);

        foreach ($hrIds as $hrId) {
            Http::attach('document', file_get_contents($docPath), basename($docPath))
                ->post($this->apiUrl . '/sendDocument', [
                    'chat_id' => (int) $hrId,
                    'caption' => "📄 {$fioShort} — kech qolish tushuntirish xati.\n💬 Sabab: {$reasonText}",
                ]);
        }

        @unlink($docPath);

        $this->sendMessage($chatId, "✅ Sababingiz qabul qilindi va tushuntirish xati tayyorlandi. Rahmat!");
    }

    /**
     * Departament kodini kompaniya kalitiga aylantiradi
     */
    private function mapDepartmentToCompany(?string $department): string
    {
        $map = [
            'EGS'    => 'egs',
            'Sebzor' => 'egs',       // kerak bo'lsa moslang
            'Navoiy' => 'egs',       // kerak bo'lsa moslang
        ];

        $key = trim($department ?? '');

        return $map[$key] ?? 'default';
    }

    private function getCompanyData(string $company): array
    {
        return match ($company) {
            'izisol' => [
                'company'  => 'IZISOL',
                'director' => 'Шукуров Р.Н.',
            ],
            'eastline' => [
                'company'  => 'EASTLINE EXPRESS',
                'director' => 'Сафаров У.А.',
            ],
            'incotruck' => [
                'company'  => 'INCOTRUCK',
                'director' => 'Тухтаев Н.К.',
            ],
            'transceka' => [
                'company'  => 'TRANSCEKA LOGISTIC SERVICES',
                'director' => 'Абдуюсупов Б.С.',
            ],
            'egs' => [
                'company'  => 'EGS group',
                'director' => 'Шукуров Р.Н.',
            ],
            default => [
                'company'  => 'Noma\'lum',
                'director' => '—',
            ],
        };
    }

    private function formatFioShort(string $fio): string
    {
        $parts = preg_split('/\s+/', trim($fio));

        $firstName = ucfirst(mb_strtolower($parts[0] ?? '', 'UTF-8'));
        $lastName  = mb_strtolower($parts[1] ?? '', 'UTF-8');

        if (!$lastName) {
            return $firstName;
        }

        if (mb_substr($lastName, 0, 2, 'UTF-8') === 'sh') {
            $initial = ucfirst(mb_substr($lastName, 0, 2, 'UTF-8'));
        } else {
            $initial = ucfirst(mb_substr($lastName, 0, 1, 'UTF-8'));
        }

        return "{$firstName} {$initial}.";
    }

    private function generateWord(array $data): string
    {
        $template = new TemplateProcessor(
            storage_path('app/templates/davomat_shablon.docx')
        );

        $template->setValue('company', $data['company']);
        $template->setValue('from', $data['director']);
        $template->setValue('employee', $data['fio']);
        $template->setValue('day', $data['day']);
        $template->setValue('month', $data['month']);
        $template->setValue('year', $data['year']);
        $template->setValue('reason', $data['reason']);
        $template->setValue('late_minutes', $data['late_minutes']);
        $template->setValue('today', now()->format('d.m.Y'));

        $dir = storage_path('app/generated');
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . '/explanation_' . time() . '.docx';
        $template->saveAs($file);

        return $file;
    }

    private function minutesToHoursMinutes(int $totalMinutes): string
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return "{$hours} soat {$minutes} daqiqa";
        }

        return "{$minutes} daqiqa";
    }
    private function sendMessage(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
