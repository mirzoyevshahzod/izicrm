<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SearchHistory;
use App\Models\Employee;

class TelegramEmployeeContoller extends Controller
{
    private string $token;

    protected string $apiUrl;

    private array $hrIds = [
        // 997696865,
        // 6337758881
        7510409703,
        6757738816,
        887162370
    ];

    public function __construct()
    {
        $this->token = config('services.telegram.contact_bot_token');
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
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

    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? null;

        if (isset($message['contact'])) {

        $contact = $message['contact'];

        // boshqa odam telefonini yuborishni oldini olish
        if ($contact['user_id'] != $chatId) {
            return;
        }

        $phone = '+' . preg_replace('/\D/', '', $contact['phone_number']);

        // employees jadvalidan tekshiramiz
        $employee = \DB::table('employees')
            ->where('work_phone', $phone)
            ->first();

        if (!$employee) {

            $this->sendMessage($chatId,
                "❌ Siz kompaniya xodimlari ro'yxatida topilmadingiz.\n".
                "Iltimos HR bilan bog'laning."
            );

            return;
        }

        // ro'yxatdan o'tkazamiz
        Contact::create([
            'chat_id' => $chatId,
            'full_name' => $employee->full_name,
            'phone_number' => $phone
        ]);

        $this->sendMessage($chatId,
            "✅ Siz muvaffaqiyatli ro'yxatdan o'tdingiz\n\n".
            "👤 {$employee->full_name}\n\n".
            "🔎 Qaysi xodimni qidiryapsiz?"
        );

        return;
    }

        $user = Contact::where('chat_id', $chatId)->first();

        $step = cache()->get("step_$chatId");

        if (!$user && !in_array($text, ['/start', '/register']) && !$step) {
            $this->sendMessage($chatId, "Botdan foydalanish uchun avval ro'yxatdan o'ting.\n/register");
            return;
        }

        $step = cache()->get("step_$chatId");

        if ($text == '/start') {

            if ($user) {

                $this->sendMessage($chatId,
                    "👋 Assalomu alaykum!\n\n".
                    "🔎 Qaysi xodimni qidiryapsiz?\n".
                    "Iltimos, xodimning ismini yoki familiyasini yozing."
                );

            } else {

                $this->sendMessage($chatId,
                    "👋 Assalomu alaykum!\n\n".
                    "Bu bot orqali kompaniya hodimlarining telefon raqamlarini topishingiz mumkin.\n\n".
                    "Ro'yxatdan o'tish uchun quyidagi komandani yuboring:\n".
                    "/register"
                );

            }

            return;
        }

        if ($text == '/register') {

            if ($user) {
                $this->sendMessage($chatId,
                    "✅ Siz allaqachon ro'yxatdan o'tgansiz.\n\n".
                    "🔎 Qaysi xodimni qidiryapsiz?"
                );
                return;
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

            Http::post($this->apiUrl.'sendMessage', [
                'chat_id' => $chatId,
                'text' => "Ro'yxatdan o'tish uchun telefon raqamingizni yuboring",
                'reply_markup' => json_encode($keyboard)
            ]);

            return;
        }


       if ($text == '/history') {

            if (!in_array($chatId, $this->hrIds)) {
                $this->sendMessage($chatId, "⛔️ Bu buyruq faqat HR uchun.");
                return;
            }

            $histories = SearchHistory::latest()->take(20)->get();

            if ($histories->isEmpty()) {
                $this->sendMessage($chatId, "❌ Hali qidiruvlar mavjud emas.");
                return;
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
             return;
        }

        

        // ▶️ HR: Employees list + search
        if ($text == '/employees') {

            if (!in_array($chatId, $this->hrIds)) {
                $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat HR uchun.");
                return;
            }

            $employees = \DB::table('employees')
                ->orderBy('full_name')
                ->get();

            if ($employees->isEmpty()) {
                $this->sendMessage($chatId, "❌ Hech qanday xodim topilmadi.");
                return;
            }

            $chunks = $employees->chunk(40); // har xabarda 40 ta

            foreach ($chunks as $chunk) {

                $message = "👥 Xodimlar ro‘yxati:\n\n";

                foreach ($chunk as $index => $employee) {

                    $phone = $employee->work_phone ?? $employee->personal_phone;

                    $message .= "- {$employee->full_name} (📞 {$phone})\n";
                }

                $this->sendMessage($chatId, $message);
            }

            return;
        }

        if ($user && $text && $text[0] !== '/') {

            $contacts = $this->searchContacts($text);

            if ($contacts->count() == 0) {
                $this->sendMessage($chatId, "❌ Bunday hodim topilmadi");
                return;
            }

            if ($contacts->count() == 1) {

                $c = $contacts->first();

                SearchHistory::create([
                    'searcher_chat_id' => $chatId,
                    'target_contact_id' => $c->id,
                    'query' => $text
                ]);

                $phone = $c->personal_phone;

                // if(!$phone){
                //     $this->sendMessage(
                //     $chatId,
                //     "Bu xodimning shaxsiy bomeri bazada mavjud emas."
                // );
                // }

                $this->sendMessage(
                    $chatId,
                    "👤 {$c->full_name}\n📞 {$phone}"
                );

                return;
            }

            $keyboard = [];

            foreach ($contacts as $c) {
                $keyboard[] = [[
                    'text' => $c->full_name,
                    'callback_data' => $c->id
                ]];
            }

            Http::post($this->apiUrl.'sendMessage', [
                'chat_id' => $chatId,
                'text' => "Qaysi hodim kerak?",
                'reply_markup' => [
                    'inline_keyboard' => $keyboard
                ]
            ]);

        }
    }

    private function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $contactId = $callback['data'];

        $contact = Employee::find($contactId);

        if (!$contact) return;

        Log::info($callback['message']);

        $phone = $contact->personal_phone;
        $text = "👤 {$contact->full_name}\n📞 {$phone}";

        // history yozish
        SearchHistory::create([
            'searcher_chat_id' => $chatId,
            'target_contact_id' => $contact->id,
            'query' => $contact->full_name
        ]);

        Http::post($this->apiUrl.'editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text
        ]);
    }
    private function toLatin($text)
    {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'j',
            'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'x','ц'=>'s',
            'ч'=>'ch','ш'=>'sh','щ'=>'sh','ъ'=>'','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',

            'А'=>'a','Б'=>'b','В'=>'v','Г'=>'g','Д'=>'d','Е'=>'e','Ё'=>'yo','Ж'=>'j',
            'З'=>'z','И'=>'i','Й'=>'y','К'=>'k','Л'=>'l','М'=>'m','Н'=>'n','О'=>'o',
            'П'=>'p','Р'=>'r','С'=>'s','Т'=>'t','У'=>'u','Ф'=>'f','Х'=>'x','Ц'=>'s',
            'Ч'=>'ch','Ш'=>'sh','Щ'=>'sh','Ъ'=>'','Ь'=>'','Э'=>'e','Ю'=>'yu','Я'=>'ya'
        ];

        return strtr($text, $map);
    }

    private function searchContacts($text)
    {
        $text = strtolower($this->toLatin(trim($text)));

        $employees = Employee::all();

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

    private function sendMessage(int $chatId, string $message){
        Http::post('https://api.telegram.org/bot' . $this->token . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }
}
