<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Feedback;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\SearchHistory;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TelegramEmployeeContoller extends Controller
{
    private string $token;

    protected string $apiUrl;

    protected string $feedbackGroupId;

    private array $hrIds = [
        997696865,
        // 6337758881
        7510409703,
        6757738816,
        887162370,
        7652330111
    ];

    public function __construct()
    {
        $this->token = config('services.telegram.contact_bot_token');
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
        $this->feedbackGroupId = config('services.telegram.feedback_group_id');
    }

    public function webhook(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }

        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? null;

        $user = Contact::where('chat_id', $chatId)->first();

        if ($this->handleCommands($chatId, $text, $user)) {
            return true;
        }

        if (isset($message['contact'])) {
            return $this->handleContactRegistration($chatId, $message['contact']);
        }


        $step = cache()->get("step_$chatId");


        if (!$user && !in_array($text, ['/start', '/register']) && !$step) {
            $this->sendMessage($chatId, "Botdan foydalanish uchun avval ro'yxatdan o'ting.\n/register");
            return true;
        }

        if ($step == 'employee_search') {
            return $this->handleEmployeeSearchStep($chatId, $text);
        }

        if ($this->handleFeedbackStep($chatId, $text, $step)) {
            return true;
        }


        if (in_array($chatId, $this->hrIds) && $step) {
            $this->handleHrStep($chatId, $text, $step);
            return true;
        }

        return false;
    }

    public function handleCommands($chatId, $text, $user)
    {
        switch ($text) {
            case '/refresh':
                $this->handleRefreshCommand($chatId);
                break;
            case '/start':
                $this->handleStartCommand($chatId, $user);
                break;
            case '/register':
                $this->handleRegisterCommand($chatId, $user);
                break;
            case '/history':
                return $this->handleHistoryCommand($chatId);
            case '/employees':
                return $this->handleEmployeesCommand($chatId);
            case '🏢 Компания ҳақида':
                $this->showCompanyInformation($chatId);
                break;
            case '🤖 Компания ботлари':
                $this->showCompanyBotInformation($chatId);
                break;
            case  '📢 Компания каналлари':
                $this->showCompanyChannelsInformation($chatId);
                break;
            case '💬 Таклиф ва шикоятлар':
                $this->storeFeedback($chatId);
                break;
            case '📞 Контактлар':
                $this->searchEmployees($chatId);
                break;
            default:
                return false;
        }

        return true;
    }

    private function handleStartCommand($chatId, $user): bool
    {
        if ($user) {


            $keyboard = [
                [
                    ['text' => '📞 Контактлар'],
                    ['text' => '🏢 Компания ҳақида'],
                ],
                [
                    ['text' => '🤖 Компания ботлари'],
                    ['text' => '📢 Компания каналлари'],
                ],
                [
                    ['text' => '💬 Таклиф ва шикоятлар'],
                ],
            ];

            Http::post('https://api.telegram.org/bot' . $this->token . '/sendMessage', [
                'chat_id' => $chatId,
                'text' => 'Қуйидагилардан бирини танланг:',
                'parse_mode' => 'html',
                'reply_markup' => json_encode([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                ])
            ]);


        } else {

            $this->sendMessage(
                $chatId,
                "👋 Assalomu alaykum!\n\n" .
                "Bu bot orqali kompaniya hodimlarining telefon raqamlarini topishingiz mumkin.\n\n" .
                "Ro'yxatdan o'tish uchun quyidagi komandani yuboring:\n/register"
            );

        }

        return true;
    }

    public function handleRegisterCommand(int $chatId, $user)
    {
        if ($user) {
            $this->sendMessage($chatId,
                "✅ Siz allaqachon ro'yxatdan o'tgansiz.\n\n" .
                "🔎 Qaysi xodimni qidiryapsiz?"
            );
            return true;
        }

        $keyboard = [
            'keyboard' => [
                [
                    [
                        'text' => "📱 Telefonni yuborish",
                        'request_contact' => true
                    ]
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        Http::post($this->apiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => "Ro'yxatdan o'tish uchun telefon raqamingizni yuboring",
            'reply_markup' => json_encode($keyboard)
        ]);

        return true;
    }

    private function handleRefreshCommand($chatId)
    {
        $this->clearHrCache($chatId);

        $this->sendMessage(
            $chatId,
            "✅ Bot holati yangilandi.\nEndi qaytadan foydalanishingiz mumkin."
        );
        return true;
    }

    public function handleHistoryCommand($chatId)
    {

        if (!in_array($chatId, $this->hrIds)) {
            $this->sendMessage($chatId, "⛔️ Bu buyruq faqat HR uchun.");
            return true;
        }

        $histories = SearchHistory::latest()->take(20)->get();

        if ($histories->isEmpty()) {
            $this->sendMessage($chatId, "❌ Hali qidiruvlar mavjud emas.");
            return true;
        }

        $chunks = $histories->chunk(10);

        foreach ($chunks as $chunk) {

            $msg = "📊 Oxirgi qidiruvlar:\n\n";

            foreach ($chunk as $h) {

                $user = Contact::where('chat_id', $h->searcher_chat_id)->first();
                $target = Employee::find($h->target_contact_id);

                if (!$user || !$target) {
                    continue;
                }

                $msg .= "👤 {$user->full_name} ";
                $msg .= "→ {$target->full_name}\n";
                // $msg .= "→ {$h->query}\n";
                $msg .= "🕒 {$h->created_at}\n\n";
            }

            $this->sendMessage($chatId, $msg);
        }
        return true;
    }

    public function handleEmployeesCommand($chatId)
    {

        if (!in_array($chatId, $this->hrIds)) {
            $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat HR uchun.");
            return true;
        }

        Http::post($this->apiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => "👨‍💼 Hodimlar boshqaruvi",
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '➕ Hodim qo\'shish',
                            'callback_data' => 'employee_add'
                        ]
                    ],
                    [
                        [
                            'text' => '✏️ Hodimni tahrirlash',
                            'callback_data' => 'employee_edit'
                        ]
                    ],
                    [
                        [
                            'text' => '🗑 Hodimni o\'chirish',
                            'callback_data' => 'employee_delete'
                        ]
                    ],
                    [
                        [
                            'text' => '📋 Hodimlar ro\'yxati',
                            'callback_data' => 'employee_list'
                        ]
                    ]
                ]
            ]
        ]);

        return true;
    }

    public function handleContactRegistration(int $chatId, array $contact)
    {

        if ($contact['user_id'] != $chatId) {
            return true;
        }

        $phone = '+' . preg_replace('/\D/', '', $contact['phone_number']);

        $employee = DB::table('employees')
            ->where('work_phone', $phone)
            ->first();

        if (!$employee) {

            $this->sendMessage($chatId,
                "❌ Siz kompaniya xodimlari ro'yxatida topilmadingiz.\n" .
                "Iltimos HR bilan bog'laning."
            );

            return true;
        }

        $user = Contact::create([
            'chat_id' => $chatId,
            'full_name' => $employee->full_name,
            'phone_number' => $phone
        ]);

        $this->handleStartCommand($chatId, $user);

        return true;
    }

    private function handleEmployeeSearch($chatId, $text): bool
    {
        $contacts = $this->searchContacts($text);

        if ($contacts->count() == 0) {
            $this->sendMessage($chatId, "❌ Bunday hodim topilmadi");
            return true;
        }

        if ($contacts->count() == 1) {

            $c = $contacts->first();

            SearchHistory::create([
                'searcher_chat_id' => $chatId,
                'target_contact_id' => $c->id,
                'query' => $text
            ]);

            $phone = $c->personal_phone;


            $this->sendMessage(
                $chatId,
                "👤 {$c->full_name}\n📞 {$phone}"
            );

            return true;
        }

        $keyboard = [];

        foreach ($contacts as $c) {
            $keyboard[] = [[
                'text' => $c->full_name,
                'callback_data' => $c->id
            ]];
        }

        Http::post($this->apiUrl . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => "Qaysi hodim kerak?",
            'reply_markup' => [
                'inline_keyboard' => $keyboard
            ]
        ]);

        return true;
    }

    public function showCompanyInformation(int $chatId): bool
    {
        $companies = Company::orderBy('name')->get();

        if ($companies->isEmpty()) {
            $this->sendMessage($chatId, "❌ Компаниялар рўйхати топилмади.");
            return true;
        }

        $message = "🏢 *КОМПАНИЯЛАР РЎЙХАТИ*\n\n";

        foreach ($companies as $company) {
            $message .= "🏷 *{$company->code}*\n";
            $message .= "🏢 {$company->name}\n";
            $message .= "🌐 {$company->website}\n\n";
        }

        $this->sendMessage($chatId, $message);

        return true;
    }

    public function showCompanyBotInformation(int $chatId): bool
    {
        $bots = TelegramBot::query()->orderBy('title')->get();

        if ($bots->isEmpty()) {
            $this->sendMessage($chatId, "❌ Компания ботлари топилмади.");
            return true;
        }

        $message = "🤖 *КОМПАНИЯ БОТЛАРИ*\n\n";

        foreach ($bots as $bot) {
            $message .= "━━━━━━━━━━━━━━━━━━\n";
            $message .= "🤖 *{$bot->title}*\n\n";
            $message .= "{$bot->description}\n\n";
            $message .= "🔗 {$bot->username}\n\n";
        }

        $this->sendMessage($chatId, $message);

        return true;
    }

    public function showCompanyChannelsInformation(int $chatId): bool
    {
        $channels = TelegramChannel::query()->orderBy('title')->get();

        if ($channels->isEmpty()) {
            $this->sendMessage($chatId, "❌ Компания каналлари топилмади.");
            return true;
        }

        $message = "📢 *КОМПАНИЯ КАНАЛЛАРИ*\n\n";

        foreach ($channels as $channel) {
            $message .= "━━━━━━━━━━━━━━━━━━\n";
            $message .= "📢 *{$channel->title}*\n\n";
            $message .= "📝 {$channel->description}\n\n";
            $message .= "🔗 {$channel->username}\n\n";
        }

        $this->sendMessage($chatId, $message);

        return true;
    }

    public function storeFeedback(int $chatId): bool
    {
        $feedback = Feedback::create([
            'bot_slug' => 'INFORMATION_BOT',
        ]);

        cache()->put("feedback_$chatId", $feedback->id);
        cache()->put("step_$chatId", "feedback_full_name");

        $this->sendMessage(
            $chatId,
            "👤 Илтимос, Ф.И.Ш.ингизни киритинг."
        );

        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function handleFeedbackStep(int $chatId, string $text, ?string $step): bool
    {

        Log::info('Feedback', [
            'step' => $step,
            'text' => $text,
        ]);

        if ($step == 'feedback_full_name') {

            Feedback::find(cache()->get("feedback_$chatId"))
                ?->update([
                    'full_name' => $text,
                ]);

            cache()->put("step_$chatId", "feedback_type");

            $keyboard = [
                'keyboard' => [
                    [['text' => '💡 Таклиф']],
                    [['text' => '⚠️ Шикоят']],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ];

            Http::post($this->apiUrl.'sendMessage',[
                'chat_id'=>$chatId,
                'text'=>"📂 Мурожаат турини танланг:",
                'reply_markup'=>json_encode($keyboard),
            ]);

            return true;
        }

        if ($step == 'feedback_type') {

            Feedback::find(cache()->get("feedback_$chatId"))
                ?->update([
                    'type' => $text == '⚠️ Шикоят'
                        ? 'complaint'
                        : 'suggestion',
                ]);

            cache()->put("step_$chatId","feedback_message");

            $this->sendMessage(
                $chatId,
                "✍️ Мурожаатингизни ёзинг."
            );

            return true;
        }

        if ($step == 'feedback_message') {

            $feedback = Feedback::find(cache()->get("feedback_$chatId"));

            $feedback?->update([
                'message' => $text,
            ]);

            $message = "📩 *Янги мурожаат*\n\n";
            $message .= "👤 *Ф.И.Ш:* {$feedback->full_name}\n";
            $message .= "📂 *Тури:* " .
                ($feedback->type == 'complaint' ? '⚠️ Шикоят' : '💡 Таклиф') . "\n\n";
            $message .= "✍️ *Матн:*\n{$text}";

            Log::info('Feedback' . $this->feedbackGroupId);
            $this->sendMessage($this->feedbackGroupId, $message);

            cache()->forget("feedback_$chatId");
            cache()->forget("step_$chatId");

            $this->sendMessage(
                $chatId,
                "✅ Раҳмат!\n\nМурожаатингиз қабул қилинди."
            );

            return true;
        }

        return false;
    }

    public function searchEmployees(int $chatId): bool
    {
        cache()->put("step_$chatId", "employee_search");

        $this->sendMessage(
            $chatId,
            "🔎 Қайси ходимни қидиряпсиз?\n\nИлтимос, ходимнинг исми ёки фамилиясини киритинг."
        );

        return true;
    }

    private function handleEmployeeSearchStep(int $chatId, string $text): bool
    {
        $this->handleEmployeeSearch($chatId, $text);

        return true;
    }

    private function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data = $callback['data'];

        if (!$this->canHandleHrAction($chatId, $data)) {
            return;
        }

        if ($this->handleEmployeeCallback($chatId, $messageId, $data)) {
            return;
        }

    }

    private function canHandleHrAction($chatId, $data): bool
    {
        $hrActions = [
            'employee_add',
            'employee_edit',
            'employee_delete',
            'employee_list'
        ];

        if (
            (in_array($data, $hrActions)
                || str_starts_with($data, 'edit_')
                || str_starts_with($data, 'delete_'))
            && !in_array($chatId, $this->hrIds)
        ) {
            return false;
        }

        return true;
    }

    private function handleEmployeeCallback($chatId, $messageId, $data): bool
    {
        if ($data == 'employee_delete') {
            return $this->handleEmployeeDelete($chatId);
        }

        if (str_starts_with($data, 'delete_')) {
            return $this->deleteEmployee($chatId, $data, $messageId);
        }

        if ($data == 'employee_edit') {
            return $this->handleEmployeeEdit($chatId);
        }

        if (str_starts_with($data, 'edit_')) {
            return $this->editEmployee($chatId, $data);
        }

        if ($data == 'employee_add') {
            return $this->handleEmployeeAdd($chatId, $messageId);
        }

        if (str_starts_with($data, 'company_')) {
            return $this->selectCompany($chatId, $messageId, $data);
        }

        if ($data == 'employee_list') {
            return $this->handleEmployeeList($chatId);
        }

        if (is_numeric($data)) {
            return $this->handleEmployeeSelect($chatId, $messageId, $data);
        }

        return false;
    }


    private function handleEmployeeDelete($chatId): bool
    {

        cache()->put("step_$chatId", 'employee_delete_search');

        $this->sendMessage(
            $chatId,
            "📱 O'chiriladigan hodimning ish telefonini kiriting:"
        );

        return true;

    }

    private function deleteEmployee(int $chatId, string $data, int $messageId): bool
    {
        $this->clearHrCache($chatId);
        $employeeId = str_replace('delete_', '', $data);

        $employee = Employee::find($employeeId);

        if (!$employee) {
            return false;
        }

        $name = $employee->full_name;

        $employee->delete();

        $this->editMessageText(
            $chatId,
            $messageId,
            "✅ {$name} o'chirildi"
        );

        return true;
    }

    private function handleEmployeeEdit(int $chatId): bool
    {
        cache()->put("step_$chatId", 'employee_edit_search');

        $this->sendMessage(
            $chatId,
            "📱 Tahrirlanadigan hodimning ish telefonini kiriting:"
        );

        return true;
    }

    private function editEmployee(int $chatId, string $data): bool
    {
        $this->clearHrCache($chatId);
        $employeeId = str_replace('edit_', '', $data);

        cache()->put("editing_employee_$chatId", $employeeId);
        cache()->put("step_$chatId", 'edit_employee_name');

        $this->sendMessage(
            $chatId,
            "👤 Yangi F.I.O kiriting:"
        );

        return true;
    }

    private function handleEmployeeAdd(int $chatId, int $messageId): bool
    {
        Http::post($this->apiUrl . 'editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => '🏢 Kompaniyani tanlang:',
            'reply_markup' => [
                'inline_keyboard' => [
                    [[
                        'text' => 'EGS',
                        'callback_data' => 'company_EGS'
                    ]],
                    [[
                        'text' => 'IZISOL',
                        'callback_data' => 'company_IZISOL'
                    ]],
                    [[
                        'text' => 'ESTLINE EXPRES',
                        'callback_data' => 'company_ESTLINE_EXPRES'
                    ]],
                    [[
                        'text' => 'TRANSEKA',
                        'callback_data' => 'company_TRANSEKA'
                    ]],
                    [[
                        'text' => 'INCUTRUCK',
                        'callback_data' => 'company_INCUTRUCK'
                    ]]
                ]
            ]
        ]);

        return true;
    }

    private function selectCompany(int $chatId, int $messageId, string $data): bool
    {

        $company = str_replace('company_', '', $data);

        cache()->put("employee_company_$chatId", $company);
        cache()->put("step_$chatId", 'employee_name');

        $this->editMessageText(
            $chatId,
            $messageId,
            "👤 Yangi hodim F.I.O sini kiriting:"
        );

        return true;
    }

    private function handleEmployeeList(int $chatId): bool
    {
        $employees = Employee::orderBy('full_name')->get();

        foreach ($employees->chunk(40) as $chunk) {

            $message = "📋 Hodimlar ro'yxati\n\n";

            foreach ($chunk as $employee) {
                $message .= "👤 {$employee->full_name}\n";
                $message .= "📞 {$employee->work_phone}\n\n";
            }

            $this->sendMessage($chatId, $message);
        }

        return true;
    }

    private function handleEmployeeSelect(int $chatId, int $messageId, string $data): bool
    {

        $contact = Employee::find($data);

        if (!$contact) {
            return false;
        }

        SearchHistory::create([
            'searcher_chat_id' => $chatId,
            'target_contact_id' => $contact->id,
            'query' => $contact->full_name
        ]);

        Http::post($this->apiUrl . 'editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => "👤 {$contact->full_name}\n📞 {$contact->personal_phone}"
        ]);

        return true;
    }

    private function searchContacts($text)
    {
        $text = strtolower($this->toLatin(trim($text)));

        $employees = Employee::all();

        Log::info('Employees searched', [
            'employees' => $employees->count()
        ]);

        Log::info(
            Employee::where('full_name', 'like', '%' . $text . '%')->pluck('full_name')
        );

        return $employees->filter(function ($employee) use ($text) {

            $name = strtolower($this->toLatin($employee->full_name));

            $words = explode(' ', $name);

            foreach ($words as $word) {

                // 1️⃣ so'z boshidan mos kelish
                if (str_starts_with($word, $text)) {
                    return true;
                }

                // 2️⃣ oddiy contains
                if (strlen($text) >= 4 && str_contains($word, $text)) {
                    return true;
                }

                // 3️⃣ fuzzy search faqat uzun so'zlarda
                if (strlen($text) >= 5 && levenshtein($text, $word) <= 1) {
                    return true;
                }
            }

            return false;

        })->values();
    }

    private function clearHrCache($chatId)
    {
        cache()->forget("step_$chatId");

        cache()->forget("employee_company_$chatId");
        cache()->forget("employee_name_$chatId");
        cache()->forget("employee_work_phone_$chatId");

        cache()->forget("editing_employee_$chatId");

        cache()->forget("new_name_$chatId");
        cache()->forget("new_work_phone_$chatId");
    }


    private function handleHrStep($chatId, $text, ?string $step)
    {

        if (str_starts_with($text, '/')) {
            return false;
        }

        if ($this->handleCreateEmployeeStep($chatId, $text, $step)) {
            return true;
        }

        if ($this->handleDeleteEmployeeStep($chatId, $text, $step)) {
            return true;
        }

        if ($this->handleEditEmployeeStep($chatId, $text, $step)) {
            return true;
        }

        return false;
    }

    private function handleCreateEmployeeStep(int $chatId, string $text, string $step): bool
    {
        if ($step == 'employee_name') {

            cache()->put("employee_name_$chatId", $text);
            cache()->put("step_$chatId", 'employee_work_phone');

            $this->sendMessage($chatId, "📱 Ish telefonini kiriting:");
            return true;
        }

        if ($step == 'employee_work_phone') {

            cache()->put("employee_work_phone_$chatId", $text);
            cache()->put("step_$chatId", 'employee_personal_phone');

            $this->sendMessage($chatId, "📞 Shaxsiy telefonini kiriting:");
            return true;
        }

        if ($step == 'employee_personal_phone') {

            Employee::create([
                'company' => cache()->get("employee_company_$chatId"),
                'full_name' => cache()->get("employee_name_$chatId"),
                'work_phone' => cache()->get("employee_work_phone_$chatId"),
                'personal_phone' => $text,
            ]);

            cache()->forget("step_$chatId");
            cache()->forget("employee_name_$chatId");
            cache()->forget("employee_work_phone_$chatId");
            cache()->forget("employee_company_$chatId");

            $this->sendMessage($chatId, "✅ Hodim muvaffaqiyatli qo'shildi");

            return true;
        }

        return false;
    }

    private function handleDeleteEmployeeStep(int $chatId, string $text, string $step): bool
    {
        if ($step == 'employee_delete_search') {

            $employees = Employee::where('work_phone', 'like', "%{$text}%")->get();

            if ($employees->isEmpty()) {

                $this->sendMessage($chatId, "❌ Hodim topilmadi");
                return false;
            }

            $keyboard = [];

            foreach ($employees as $employee) {

                $keyboard[] = [[
                    'text' => "{$employee->full_name} ({$employee->work_phone})",
                    'callback_data' => 'delete_' . $employee->id
                ]];
            }

            Http::post($this->apiUrl . 'sendMessage', [
                'chat_id' => $chatId,
                'text' => "O'chiriladigan hodimni tanlang:",
                'reply_markup' => [
                    'inline_keyboard' => $keyboard
                ]
            ]);

            return true;
        }

        return false;

    }

    private function handleEditEmployeeStep(int $chatId, string $text, string $step): bool
    {
        if ($step == 'employee_edit_search') {

            $employees = Employee::where('work_phone', 'like', "%{$text}%")->get();

            if ($employees->isEmpty()) {

                $this->sendMessage($chatId, "❌ Hodim topilmadi");
                return false;
            }

            $keyboard = [];

            foreach ($employees as $employee) {

                $keyboard[] = [[
                    'text' => "{$employee->full_name} ({$employee->work_phone})",
                    'callback_data' => 'edit_' . $employee->id
                ]];
            }

            Http::post($this->apiUrl . 'sendMessage', [
                'chat_id' => $chatId,
                'text' => "Tahrirlanadigan hodimni tanlang:",
                'reply_markup' => [
                    'inline_keyboard' => $keyboard
                ]
            ]);

            return true;
        }

        if ($step == 'edit_employee_name') {

            cache()->put("new_name_$chatId", $text);
            cache()->put("step_$chatId", 'edit_employee_work_phone');

            $this->sendMessage($chatId, "📱 Yangi ish telefonini kiriting:");

            return true;
        }

        if ($step == 'edit_employee_work_phone') {

            cache()->put("new_work_phone_$chatId", $text);
            cache()->put("step_$chatId", 'edit_employee_personal_phone');

            $this->sendMessage($chatId, "📞 Yangi shaxsiy telefonni kiriting:");

            return true;
        }

        if ($step == 'edit_employee_personal_phone') {

            $employee = Employee::find(
                cache()->get("editing_employee_$chatId")
            );

            if ($employee) {

                $employee->update([
                    'full_name' => cache()->get("new_name_$chatId"),
                    'work_phone' => cache()->get("new_work_phone_$chatId"),
                    'personal_phone' => $text,
                ]);
            }

            cache()->forget("step_$chatId");
            cache()->forget("editing_employee_$chatId");
            cache()->forget("new_name_$chatId");
            cache()->forget("new_work_phone_$chatId");

            $this->sendMessage(
                $chatId,
                "✅ Hodim ma'lumotlari yangilandi"
            );

            return true;
        }

        return false;
    }


    private function toLatin($text)
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'j',
            'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'x', 'ц' => 's',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',

            'А' => 'a', 'Б' => 'b', 'В' => 'v', 'Г' => 'g', 'Д' => 'd', 'Е' => 'e', 'Ё' => 'yo', 'Ж' => 'j',
            'З' => 'z', 'И' => 'i', 'Й' => 'y', 'К' => 'k', 'Л' => 'l', 'М' => 'm', 'Н' => 'n', 'О' => 'o',
            'П' => 'p', 'Р' => 'r', 'С' => 's', 'Т' => 't', 'У' => 'u', 'Ф' => 'f', 'Х' => 'x', 'Ц' => 's',
            'Ч' => 'ch', 'Ш' => 'sh', 'Щ' => 'sh', 'Ъ' => '', 'Ь' => '', 'Э' => 'e', 'Ю' => 'yu', 'Я' => 'ya'
        ];

        return strtr($text, $map);
    }

    private function sendMessage(int $chatId, string $message)
    {
        Http::post('https://api.telegram.org/bot' . $this->token . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }

    private function editMessageText(int $chatId, int $messageId, string $message, array $options = [])
    {
        Http::post('https://api.telegram.org/bot' . $this->token . '/editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ], $options));
    }
}
