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
        $text = $message['text'] ?? null;

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
                    "✅ Raqam qabul qilindi.\n\n📝 Endi ism-familiyangizni YOKI HR bergan ID raqamingizni kiriting.\n\nMasalan: Firdavs Raxmatov\nyoki: 0000000021"
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

        // 🔵 HR: qidiruv so'rovi kutilmoqda
        $hrSearchState = cache()->get("hr_search_state_{$chatId}");

        if ($hrSearchState === 'waiting_query' && $text) {
            $this->handleHrSearchQuery($chatId, $text);
            return response()->json(['ok' => true]);
        }

        // 🔵 HR: yangi xodim qo'shish oqimi
        $hrAddState = cache()->get("hr_add_state_{$chatId}");

        if ($hrAddState && $text) {
            $this->handleHrAddStep($chatId, $hrAddState, $text);
            return response()->json(['ok' => true]);
        }

        // 🔵 HR: tahrirlash oqimi
        $hrEditState = cache()->get("hr_edit_state_{$chatId}");

        if ($hrEditState && $text) {
            $this->handleHrEditStep($chatId, $hrEditState, $text);
            return response()->json(['ok' => true]);
        }

        // ▶️ HR: /xodimlar menyusi
        if ($text === '/xodimlar') {
            $hrIds = array_map('intval', config('services.telegram.egs_hr_ids', []));

            if (!in_array($chatId, $hrIds, true)) {
                $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat HR uchun.");
                return response()->json(['ok' => true]);
            }

            $this->showHrMenu($chatId);
            return response()->json(['ok' => true]);
        }

        // ▶️ /start
        if ($text === '/start') {
            $bossId = config('services.telegram.egs_boss_ids', []);

            if (in_array($chatId, $bossId)) {
                $this->sendMessage(
                    $chatId,
                    "👋 Assalomu alaykum!\n\n"
                    . "Sizga xodimlarning davomat holati bo‘yicha xabarnomalar yuborib boriladi. 📊"
                );
                return response()->json(['ok' => true]);
            }

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
    /**
     * Ism-familiya YOKI person_id kiritilganda — bazadan qidirib, topilsa chat_id va phone yozadi
     */
    private function handleFioInput(int $chatId, string $text): void
    {
        $text = trim($text);

        if (empty($text)) {
            $this->sendMessage($chatId, "❌ Iltimos, ism-familiyangizni yoki ID raqamingizni kiriting.");
            return;
        }

        // 1️⃣ Avval — bu ID raqammi? (faqat raqamlardan iborat bo'lsa)
        if (ctype_digit($text)) {
            $matched = $this->findByPersonId($text);

            if (!$matched) {
                $this->sendMessage(
                    $chatId,
                    "❌ Bunday ID raqamli xodim topilmadi.\n\n"
                    . "Iltimos, ID raqamingizni tekshirib qayta yuboring, yoki ism-familiyangizni yozing."
                );
                return;
            }

            $this->completeRegistration($chatId, $matched);
            return;
        }

        // 2️⃣ Aks holda — ism-familiya sifatida qidiramiz
        $normalizedInput = $this->normalizeName($text);

        if (empty($normalizedInput)) {
            $this->sendMessage($chatId, "❌ Iltimos, ism va familiyangizni to'g'ri kiriting.\n\nMasalan: Firdavs Raxmatov");
            return;
        }

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
                . "Iltimos, to'g'ri yozganingizga ishonch hosil qiling (masalan: Firdavs Raxmatov), "
                . "yoki HR bergan ID raqamingizni yuboring, yoki HR bilan bog'laning."
            );
            return;
        }

        $this->completeRegistration($chatId, $matched);
    }

    /**
     * Kiritilgan raqamni person_id bilan solishtiradi (nol bilan to'ldirilgan yoki oddiy)
     */
    private function findByPersonId(string $inputId): ?object
    {
        // to'g'ridan-to'g'ri moslik
        $matched = DB::table('attendance_employees')
            ->whereNull('chat_id')
            ->where('person_id', $inputId)
            ->first(['id', 'person_id', 'first_name', 'last_name']);

        if ($matched) {
            return $matched;
        }

        // person_id 10 xonali, nol bilan to'ldirilgan (masalan "21" -> "0000000021")
        $padded = str_pad($inputId, 10, '0', STR_PAD_LEFT);

        return DB::table('attendance_employees')
            ->whereNull('chat_id')
            ->where('person_id', $padded)
            ->first(['id', 'person_id', 'first_name', 'last_name']);
    }

    /**
     * Ro'yxatdan o'tishni yakunlaydi — chat_id va phone yozadi
     */
    /**
     * Ro'yxatdan o'tishni yakunlaydi — chat_id va phone yozadi
     */
    private function completeRegistration(int $chatId, object $matched): void
    {
        $phone = cache()->get("egs_register_phone_{$chatId}");

        DB::table('attendance_employees')
            ->where('id', $matched->id)
            ->update([
                'chat_id' => $chatId,
                'phone' => $phone,
                'updated_at' => now(),
            ]);

        cache()->forget("egs_register_state_{$chatId}");
        cache()->forget("egs_register_phone_{$chatId}");

        // ✅ Klaviaturani (telefon yuborish tugmasini) olib tashlaymiz
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => "✅ Muvaffaqiyatli ro'yxatdan o'tdingiz!\n\n👤 {$matched->first_name} {$matched->last_name}\n\nEndi kech qolganingizda bot sizga avtomatik xabar yuboradi.",
            'reply_markup' => [
                'remove_keyboard' => true,
            ],
        ]);
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
            'text' => $text,
            'reply_markup' => [
                'keyboard' => [
                    [
                        ['text' => '📱 Raqamni yuborish', 'request_contact' => true],
                    ],
                ],
                'resize_keyboard' => true,
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
        $data = $callback['data'] ?? '';
        $messageId = $callback['message']['message_id'] ?? null;

        // ============ HR: XODIMLAR BOSHQARUVI ============

        if ($data === 'hr_menu') {
            cache()->forget("hr_add_state_{$chatId}");
            cache()->forget("hr_add_data_{$chatId}");
            cache()->forget("hr_edit_state_{$chatId}");
            cache()->forget("hr_search_state_{$chatId}");

            Http::post($this->apiUrl . '/editMessageText', [
                'chat_id'    => $chatId,
                'message_id' => $messageId,
                'text'       => "👥 *Xodimlar boshqaruvi*\n\nKerakli amalni tanlang:",
                'parse_mode' => 'Markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [['text' => '➕ Yangi xodim qo\'shish', 'callback_data' => 'hr_add']],
                        [['text' => '🔍 Qidirish', 'callback_data' => 'hr_search']],
                    ],
                ],
            ]);
            return;
        }

        if (str_starts_with($data, 'hr_') && $this->isHr($chatId)) {

            if ($data === 'hr_add') {
                $this->startHrAdd($chatId, $messageId);
                return;
            }

            if ($data === 'hr_search') {
                $this->showSearchOptions($chatId, $messageId);
                return;
            }

            if ($data === 'hr_search_department') {
                $this->showDepartmentList($chatId, $messageId);
                return;
            }

            if ($data === 'hr_search_params') {
                cache()->put("hr_search_state_{$chatId}", 'waiting_query', 600);
                Http::post($this->apiUrl . '/editMessageText', [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => "✍️ Ism, familiya yoki person_id kiriting:",
                ]);
                return;
            }

            if (str_starts_with($data, 'hr_dept_')) {
                $department = base64_decode(substr($data, strlen('hr_dept_')));
                $this->showEmployeesByDepartment($chatId, $department);
                return;
            }

            if ($data === 'hr_list_all') {
                $employees = DB::table('attendance_employees')->orderBy('department')->orderBy('first_name')->get();
                $this->sendEmployeeResults($chatId, $employees, "📋 Barcha xodimlar:");
                return;
            }

            if (str_starts_with($data, 'hr_edit_')) {
                $employeeId = (int) substr($data, strlen('hr_edit_'));
                $this->showEmployeeCard($chatId, $employeeId, $messageId);
                return;
            }

            if (str_starts_with($data, 'hr_editfield_name_')) {
                $employeeId = (int) substr($data, strlen('hr_editfield_name_'));
                cache()->put("hr_edit_state_{$chatId}", "name_{$employeeId}", 600);
                Http::post($this->apiUrl . '/editMessageText', [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => "📝 Yangi ism-familiyani kiriting:",
                ]);
                return;
            }

            if (str_starts_with($data, 'hr_editfield_dept_')) {
                $employeeId = (int) substr($data, strlen('hr_editfield_dept_'));
                cache()->put("hr_edit_state_{$chatId}", "dept_{$employeeId}", 600);
                Http::post($this->apiUrl . '/editMessageText', [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => "🏢 Yangi departamentni kiriting:",
                ]);
                return;
            }

            if (str_starts_with($data, 'hr_reset_chat_')) {
                $employeeId = (int) substr($data, strlen('hr_reset_chat_'));
                DB::table('attendance_employees')->where('id', $employeeId)->update([
                    'chat_id'    => null,
                    'phone'      => null,
                    'updated_at' => now(),
                ]);
                $this->showEmployeeCard($chatId, $employeeId, $messageId);
                return;
            }

            if (str_starts_with($data, 'hr_delete_confirm_')) {
                $employeeId = (int) substr($data, strlen('hr_delete_confirm_'));
                DB::table('attendance_employees')->where('id', $employeeId)->delete();
                Http::post($this->apiUrl . '/editMessageText', [
                    'chat_id'    => $chatId,
                    'message_id' => $messageId,
                    'text'       => "🗑 Xodim o'chirildi.",
                ]);
                return;
            }

            if (str_starts_with($data, 'hr_delete_')) {
                $employeeId = (int) substr($data, strlen('hr_delete_'));
                $this->confirmDelete($chatId, $employeeId, $messageId);
                return;
            }
        }


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
                    'company' => $companyKey,
                    'status' => 'waiting_reason',
                    'updated_at' => now(),
                ]);

            Http::post($this->apiUrl . '/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text' => "✅ Kompaniya tanlandi.\n\n✍️ Endi kech qolish sababingizni yozing:",
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
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => "✍️ Iltimos, kompaniyangizni tanlang:",
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
                'reason' => $reasonText,
                'status' => 'completed',
                'updated_at' => now(),
            ]);

        // ⬇️ Endi xodim tanlagan kompaniyadan olinadi (department'dan emas)
        $companyData = $this->getCompanyData($lateEvent->company);

        $fioShort = $this->formatFioShort($lateEvent->fio);

        DB::table('attendances')->insert([
            'chat_id' => $chatId,
            'fio' => $fioShort,
            'company' => $companyData['company'],
            'day' => $lateEvent->day,
            'month' => $lateEvent->month,
            'year' => $lateEvent->year,
            'reason' => $reasonText,
            'late_minutes' => $lateEvent->late_minutes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $docPath = $this->generateWord([
            'company' => $companyData['company'],
            'director' => $companyData['director'],
            'fio' => $fioShort,
            'day' => sprintf('%02d', $lateEvent->day),
            'month' => sprintf('%02d', $lateEvent->month),
            'year' => $lateEvent->year,
            'late_minutes' => $this->minutesToHoursMinutes($lateEvent->late_minutes),
            'reason' => $reasonText,
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
                    'chat_id' => (int)$hrId,
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
            'EGS' => 'egs',
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
                'company' => 'IZISOL',
                'director' => 'Шукуров Р.Н.',
            ],
            'eastline' => [
                'company' => 'EASTLINE EXPRESS',
                'director' => 'Сафаров У.А.',
            ],
            'incotruck' => [
                'company' => 'INCOTRUCK',
                'director' => 'Тухтаев Н.К.',
            ],
            'transceka' => [
                'company' => 'TRANSCEKA LOGISTIC SERVICES',
                'director' => 'Абдуюсупов Б.С.',
            ],
            'egs' => [
                'company' => 'EGS group',
                'director' => 'Шукуров Р.Н.',
            ],
            default => [
                'company' => 'Noma\'lum',
                'director' => '—',
            ],
        };
    }

    private function formatFioShort(string $fio): string
    {
        $parts = preg_split('/\s+/', trim($fio));

        $firstName = ucfirst(mb_strtolower($parts[0] ?? '', 'UTF-8'));
        $lastName = mb_strtolower($parts[1] ?? '', 'UTF-8');

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
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return "{$hours} soat {$minutes} daqiqa";
        }

        return "{$minutes} daqiqa";
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    /**
     * =========================
     * HR: XODIMLAR CRUD BLOKI
     * =========================
     */

    private function isHr(int $chatId): bool
    {
        $hrIds = array_map('intval', config('services.telegram.egs_hr_ids', []));
        return in_array($chatId, $hrIds, true);
    }

    private function showHrMenu(int $chatId): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id' => $chatId,
            'text'    => "👥 *Xodimlar boshqaruvi*\n\nKerakli amalni tanlang:",
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [['text' => '➕ Yangi xodim qo\'shish', 'callback_data' => 'hr_add']],
                    [['text' => '🔍 Qidirish', 'callback_data' => 'hr_search']],
                ],
            ],
        ]);
    }

    /**
     * --- QIDIRISH ---
     */

    private function showSearchOptions(int $chatId, ?int $messageId = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text'    => "🔍 Qidiruv turini tanlang:",
            'reply_markup' => [
                'inline_keyboard' => [
                    [['text' => '🏢 Departament bo\'yicha', 'callback_data' => 'hr_search_department']],
                    [['text' => '👤 Ism/Familiya/ID bo\'yicha', 'callback_data' => 'hr_search_params']],
                    [['text' => '◀️ Ortga', 'callback_data' => 'hr_menu']],
                ],
            ],
        ];

        if ($messageId) {
            $payload['message_id'] = $messageId;
            Http::post($this->apiUrl . '/editMessageText', $payload);
        } else {
            Http::post($this->apiUrl . '/sendMessage', $payload);
        }
    }

    private function showDepartmentList(int $chatId, int $messageId): void
    {
        $departments = DB::table('attendance_employees')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        if ($departments->isEmpty()) {
            $this->sendMessage($chatId, "❌ Departamentlar topilmadi.");
            return;
        }

        $buttons = [];
        foreach ($departments as $dept) {
            $buttons[] = [[
                'text' => $dept,
                'callback_data' => 'hr_dept_' . base64_encode($dept),
            ]];
        }

        $buttons[] = [['text' => '◀️ Ortga', 'callback_data' => 'hr_search']];

        Http::post($this->apiUrl . '/editMessageText', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "🏢 Departamentni tanlang:",
            'reply_markup' => ['inline_keyboard' => $buttons],
        ]);
    }

    private function showEmployeesByDepartment(int $chatId, string $department): void
    {
        $employees = DB::table('attendance_employees')
            ->where('department', $department)
            ->orderBy('first_name')
            ->get();

        $this->sendEmployeeResults($chatId, $employees, "🏢 *{$department}* bo'limi xodimlari:");
    }

    private function handleHrSearchQuery(int $chatId, string $query): void
    {
        cache()->forget("hr_search_state_{$chatId}");

        $query = trim($query);

        $employees = DB::table('attendance_employees')
            ->where('first_name', 'LIKE', "%{$query}%")
            ->orWhere('last_name', 'LIKE', "%{$query}%")
            ->orWhere('person_id', 'LIKE', "%{$query}%")
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        $this->sendEmployeeResults($chatId, $employees, "🔍 \"{$query}\" bo'yicha natijalar:");
    }

    private function sendEmployeeResults(int $chatId, $employees, string $title): void
    {
        if ($employees->isEmpty()) {
            $this->sendMessage($chatId, "❌ Hech qanday xodim topilmadi.");
            return;
        }

        $text = $title . "\n\n";

        foreach ($employees as $index => $employee) {
            $status = $employee->chat_id ? '✅' : '⚪️';
            $text .= ($index + 1) . ". {$status} {$employee->first_name} {$employee->last_name}\n"
                . "   🆔 {$employee->person_id} | 🏢 {$employee->department}\n";
        }

        $text .= "\n✅ — botda ro'yxatdan o'tgan, ⚪️ — o'tmagan";

        $buttons = [];
        foreach ($employees->take(8) as $employee) {
            $buttons[] = [[
                'text' => "✏️ {$employee->first_name} {$employee->last_name}",
                'callback_data' => 'hr_edit_' . $employee->id,
            ]];
        }

        $buttons[] = [['text' => '◀️ Ortga', 'callback_data' => 'hr_search']];

        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'reply_markup' => ['inline_keyboard' => $buttons],
        ]);
    }

    /**
     * --- QO'SHISH ---
     */

    private function startHrAdd(int $chatId, int $messageId): void
    {
        cache()->put("hr_add_state_{$chatId}", 'waiting_person_id', 600);
        cache()->put("hr_add_data_{$chatId}", [], 600);

        Http::post($this->apiUrl . '/editMessageText', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "🆔 Yangi xodimning HikCentral person_id raqamini kiriting:\n\nMasalan: 0000000131",
            'reply_markup' => [
                'inline_keyboard' => [
                    [['text' => '◀️ Ortga', 'callback_data' => 'hr_menu']],
                ],
            ],
        ]);
    }

    private function handleHrAddStep(int $chatId, string $state, string $text): void
    {
        $text = trim($text);
        $data = cache()->get("hr_add_data_{$chatId}", []);

        if ($state === 'waiting_person_id') {

            $exists = DB::table('attendance_employees')->where('person_id', $text)->exists();

            if ($exists) {
                $this->sendMessage($chatId, "❌ Bu person_id allaqachon mavjud. Boshqa ID kiriting:");
                return;
            }

            $data['person_id'] = $text;
            cache()->put("hr_add_data_{$chatId}", $data, 600);
            cache()->put("hr_add_state_{$chatId}", 'waiting_name', 600);

            $this->sendMessage($chatId, "📝 Endi ism-familiyasini kiriting:\n\nMasalan: Firdavs Raxmatov");
            return;
        }

        if ($state === 'waiting_name') {

            $parts = preg_split('/\s+/', $text, 2);
            $data['first_name'] = $parts[0] ?? $text;
            $data['last_name']  = $parts[1] ?? '';

            cache()->put("hr_add_data_{$chatId}", $data, 600);
            cache()->put("hr_add_state_{$chatId}", 'waiting_department', 600);

            $this->sendMessage($chatId, "🏢 Endi departamentini kiriting:\n\nMasalan: EGS");
            return;
        }

        if ($state === 'waiting_department') {

            $data['department'] = $text;

            DB::table('attendance_employees')->insert([
                'person_id'  => $data['person_id'],
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'department' => $data['department'],
                'chat_id'    => null,
                'phone'      => null,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            cache()->forget("hr_add_state_{$chatId}");
            cache()->forget("hr_add_data_{$chatId}");

            $this->sendMessage(
                $chatId,
                "✅ Yangi xodim qo'shildi!\n\n"
                ."🆔 {$data['person_id']}\n"
                ."👤 {$data['first_name']} {$data['last_name']}\n"
                ."🏢 {$data['department']}"
            );
            return;
        }
    }

    /**
     * --- TAHRIRLASH / O'CHIRISH ---
     */

    private function showEmployeeCard(int $chatId, int $employeeId, ?int $messageId = null): void
    {
        $employee = DB::table('attendance_employees')->where('id', $employeeId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Xodim topilmadi.");
            return;
        }

        $status = $employee->chat_id ? "✅ Ro'yxatdan o'tgan" : "⚪️ Ro'yxatdan o'tmagan";

        $text = "👤 *{$employee->first_name} {$employee->last_name}*\n\n"
            ."🆔 Person ID: {$employee->person_id}\n"
            ."🏢 Departament: {$employee->department}\n"
            ."📱 Telefon: " . ($employee->phone ?? '—') . "\n"
            ."📌 Holat: {$status}";

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '✏️ Ism-familiya', 'callback_data' => "hr_editfield_name_{$employee->id}"],
                        ['text' => '🏢 Departament', 'callback_data' => "hr_editfield_dept_{$employee->id}"],
                    ],
                    [
                        ['text' => '🔄 Chat_id tozalash', 'callback_data' => "hr_reset_chat_{$employee->id}"],
                    ],
                    [
                        ['text' => '🗑 O\'chirish', 'callback_data' => "hr_delete_{$employee->id}"],
                    ],
                    [
                        ['text' => '◀️ Ortga', 'callback_data' => 'hr_menu'],
                    ],
                ],
            ],
        ];

        if ($messageId) {
            $payload['message_id'] = $messageId;
            Http::post($this->apiUrl . '/editMessageText', $payload);
        } else {
            Http::post($this->apiUrl . '/sendMessage', $payload);
        }
    }

    private function handleHrEditStep(int $chatId, string $state, string $text): void
    {
        // state format: "name_<id>" yoki "dept_<id>"
        [$field, $employeeId] = explode('_', $state, 2);
        $text = trim($text);

        if ($field === 'name') {
            $parts = preg_split('/\s+/', $text, 2);

            DB::table('attendance_employees')->where('id', $employeeId)->update([
                'first_name' => $parts[0] ?? $text,
                'last_name'  => $parts[1] ?? '',
                'updated_at' => now(),
            ]);
        } elseif ($field === 'dept') {
            DB::table('attendance_employees')->where('id', $employeeId)->update([
                'department' => $text,
                'updated_at' => now(),
            ]);
        }

        cache()->forget("hr_edit_state_{$chatId}");

        $this->sendMessage($chatId, "✅ Yangilandi!");
        $this->showEmployeeCard($chatId, (int) $employeeId);
    }

    private function confirmDelete(int $chatId, int $employeeId, int $messageId): void
    {
        $employee = DB::table('attendance_employees')->where('id', $employeeId)->first();

        if (!$employee) {
            $this->sendMessage($chatId, "❌ Xodim topilmadi.");
            return;
        }

        Http::post($this->apiUrl . '/editMessageText', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => "⚠️ {$employee->first_name} {$employee->last_name}ni rostdan o'chirmoqchimisiz?",
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        ['text' => '✅ Ha, o\'chirish', 'callback_data' => "hr_delete_confirm_{$employeeId}"],
                        ['text' => '❌ Bekor qilish', 'callback_data' => "hr_edit_{$employeeId}"],
                    ],
                ],
            ],
        ]);
    }
}
