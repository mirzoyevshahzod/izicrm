<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\Department;
use App\Models\DepartmentHead;
use App\Models\InformationLink;
use App\Models\ContactBotUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Company;
use Illuminate\Support\Facades\Log;


class TelegramContactBotController extends Controller
{
    protected $botToken;

    protected $groupChatId;

    protected $apiUrl;

    public function __construct(){
        $this->groupChatId = config('services.telegram.feedback_group_id');
        $this->botToken = config('services.telegram.contact_as_bot_token');
        $this->apiUrl = 'https://api.telegram.org/bot'.$this->botToken.'/';
    }
    //
    public function webhook(Request $request)
    {
        $update = $request->all();

        Log::info('Response', [
            'response' => $update,
    ]);

        if (isset($update['message'])) {

            $data['chatId'] = $update['message']['chat']['id'];
            $telegramId = $update['message']['from']['id'];
            $data['username'] = $update['message']['from']['username'] ?? null;
            $data['lastName'] = $update['message']['from']['last_name'] ?? null;
            $data['firstName'] = $update['message']['from']['first_name'] ?? null;
            $text = trim($update['message']['text'] ?? '');

            if (str_starts_with($text, '/start')) {

                $this->handleStart($data, $telegramId, $text);

                return response()->json(['ok' => true]);
            }

            $user = ContactBotUser::where('telegram_id', $telegramId)->first();

            if (!$user) {
                $this->sendMessage(
                    $data['chatId'],
                    "❌ Siz ro'yxatdan o'tmagansiz. Iltimos /start buyrug'ini havola orqali qayta bosing."
                );

                return response()->json(['ok' => true]);
            }

            if ($text == '📞 Контакт маълумоти') {

                $this->showDepartmentHead($user);

                return response()->json(['ok' => true]);
            }

            if ($text == '📝 Шикоят қолдириш') {

                $user->update([
                    'state' => 'appeal_company'
                ]);

                $this->sendMessage(
                    $data['chatId'],
                    "🏢 Қайси компанияда ишлайсиз?"
                );

                return response()->json(['ok' => true]);
            }

            if ($user && $user->state == 'appeal_company') {

                Appeal::create([
                    'user_id' => $user->id,
                    'company_name' => $text,
                ]);

                $user->update([
                    'state' => 'appeal_phone'
                ]);

                $this->sendMessage(
                    $data['chatId'],
                    "📞 Телефон рақамингизни киритинг."
                );

                return response()->json(['ok' => true]);
            }

            if ($user && $user->state == 'appeal_phone') {

                $appeal = Appeal::where('user_id',$user->id)
                    ->latest()
                    ->first();


                if (!preg_match('/^\+998\d{9}$/', $text)) {

                    $this->sendMessage(
                        $data['chatId'],
                        "❌ Телефон рақамини +998XXXXXXXXX кўринишида киритинг."
                    );

                    return response()->json(['ok' => true]);
                }

                $appeal->update([
                    'Phone' => $text,
                ]);

                $user->update([
                    'state' => 'appeal_message'
                ]);

                $this->sendMessage(
                    $data['chatId'],
                    "📝 Шикоятингизни ёзинг."
                );

                return response()->json(['ok' => true]);
            }

            if ($user && $user->state == 'appeal_message') {

                $appeal = Appeal::where('user_id',$user->id)
                    ->latest()
                    ->first();

                $appeal->update([
                    'Message' => $text,
                    'type' => 'complaint',
                ]);

                $user->update([
                    'state' => null,
                ]);

                $appeal->refresh();

                $senderType = $user->type === 'sales'
                    ? '👤 Клиент'
                    : '🚛 Ҳайдовчи';

                $adminText = "📩 Янги шикоят келиб тушди\n\n"
                    ."👥 Ким томонидан: {$senderType}\n"
                    ."👤 Ф.И.Ш: {$user->name}\n"
                    ."🏢 Компания: {$appeal->Company_name}\n"
                    ."📞 Телефон: {$appeal->Phone}\n"
                    ."💬 Шикоят: {$appeal->Message}\n\n"
                    ."🕒 ".now()->format('d.m.Y H:i');
                $this->sendMessage($this->groupChatId, $adminText);

                $this->sendMessage(
                    $data['chatId'],
                    "✅ Мурожаатингиз қабул қилинди."
                );

                return response()->json(['ok' => true]);
            }

            if ($text == 'ℹ️ Маълумотлар') {

                $this->showInformationLinks($user);

                return response()->json(['ok' => true]);
            }

            return response()->json(['ok' => true]);
        }


        return response()->json(['ok' => true]);
    }

    public function handleStart(array $data, $telegramId, $text)
    {
        $parts = explode(' ', $text);

        if (isset($parts[1])) {

            $map = [
                'es' => ['slug' => 'egs', 'type' => 'sales'],
                'eo' => ['slug' => 'egs', 'type' => 'operations'],

                'ts' => ['slug' => 'tls', 'type' => 'sales'],
                'to' => ['slug' => 'tls', 'type' => 'operations'],

                'xs' => ['slug' => 'exp', 'type' => 'sales'],
                'xo' => ['slug' => 'exp', 'type' => 'operations'],
            ];

            $key = strtolower($parts[1]);

            if (!isset($map[$key])) {
                $this->sendMessage(
                    $data['chatId'],
                    "❌ Нотўғри ҳавола."
                );
                return;
            }

            $slug = $map[$key]['slug'];
            $type = $map[$key]['type'];

            if (!in_array($type, ['sales', 'operations'])) {
                $this->sendMessage(
                    $data['chatId'],
                    "❌ Нотўғри ҳавола."
                );
                return;
            }

            $company = Company::where('code', $slug)->first();

            if (!$company) {
                $this->sendMessage($data['chatId'], "❌ Kompaniya topilmadi.");
                return;
            }

            $telegramUser = ContactBotUser::updateOrCreate(
                [
                    'telegram_id' => $telegramId,
                ],
                [
                    'company_slug' => $slug,
                    'type' => $type,
                    'name' => trim(($data['lastName'] ?? '') . ' ' . ($data['firstName'] ?? '')),
                    'last_used_at' => now(),
                    'state' => null
                ]
            );
        }else {

            // oddiy /start


            $telegramUser = ContactBotUser::query()->where('telegram_id', $telegramId)->first();


            if (!$telegramUser) {

                $this->sendMessage(
                    $data['chatId'],
                    "❌ Siz hali kompaniyaga bog'lanmagansiz.\n\nIltimos havola orqali botga kiring."
                );

                return;
            }

            $telegramUser->update([
                'state' => null,
            ]);

        }

        $this->showCompanyInfo($telegramUser);

        $this->showMenu($telegramUser);
    }


    private function showMenu(ContactBotUser $user)
    {
        $keyboard = [
            [
                ['text' => '📞 Контакт маълумоти'],
                ['text' => '📝 Шикоят қолдириш'],
            ]
        ];

        if ($user->type === 'operations') {
            $keyboard[] = [
                ['text' => 'ℹ️ Маълумотлар'],
            ];
        }

        Http::post($this->apiUrl.'sendMessage',[
            'chat_id' => $user->telegram_id,
            'text' => "Қуйидагилардан бирини танланг:",
            'reply_markup' => json_encode([
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
            ])
        ]);
    }

    private function showDepartmentHead(ContactBotUser $user)
    {

        $chatId = $user->telegram_id;

        $company = Company::query()->where('code', $user->company_slug)->first();

        if (!$company) {
            $this->sendMessage($chatId, "❌ Компания топилмади.");
            return;
        }

        $department = Department::query()
            ->where('company_id', $company->id)
            ->where('name', $user->type)
            ->first();

        if (!$department) {
            $this->sendMessage($chatId, "❌ Siz {$user->type} бўлими топилмади.");
            return;
        }

        $head = DepartmentHead::query()->where('department_id', $department->id)->first();

        if (!$head) {
            $this->sendMessage($chatId, "❌ Бўлим масъули топилмади.");
            return;
        }

        $text = "📋 Бўлим масъулининг алоқа маълумотлари\n\n"
            ."👨‍💼 Ф.И.Ш: {$head->full_name}\n"
            ."💼 Лавозими: {$head->position}\n"
            ."📞 Телефон: {$head->phone}\n"
            ."📱 Telegram: {$head->telegram}\n"
            ."📧 Email: {$head->email}\n\n"
            ."🤝 Мурожаат қилишингиз мумкин.";

        Http::post($this->apiUrl.'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }

    private function showInformationLinks(ContactBotUser $user)
    {
        $links = InformationLink::where('type', $user->type)->get();

        if ($links->isEmpty()) {
            $this->sendMessage(
                $user->telegram_id,
                "ℹ️ Ҳозирча маълумотлар мавжуд эмас."
            );

            return;
        }

        $keyboard = [];

        foreach ($links as $link) {
            $keyboard[] = [[
                'text' => $link->title,
                'url'  => $link->url,
            ]];
        }

        Http::post($this->apiUrl.'sendMessage', [
            'chat_id' => $user->telegram_id,
            'text' => "📚 Қуйидаги маълумотлардан бирини танланг:",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ]),
        ]);
    }

    private function showCompanyInfo(ContactBotUser $user)
    {
        $company = Company::where('code', $user->company_slug)->first();

        if (!$company) {
            return;
        }

        $caption = "🏢 <b>{$company->name}</b>\n\n";

        if ($company->description) {
            $caption .= "📝 {$company->description}\n\n";
        }

        if ($company->address) {
            $caption .= "📍 {$company->address}\n";
        }

        if ($company->phone) {
            $caption .= "📞 {$company->phone}\n";
        }

        if ($company->website) {
            $caption .= "🌐 {$company->website}\n";
        }

        if ($company->email) {
            $caption .= "📧 {$company->email}\n";
        }

        if ($company->instagram) {
            $caption .= "📷 {$company->instagram}\n";
        }

        $file = storage_path('app/public/' . $company->logo_path);

        // Logo mavjud bo'lsa rasm bilan yuborish
        if (!empty($company->logo_path) && file_exists($file)) {

            Http::attach(
                'photo',
                fopen($file, 'r'),
                basename($file)
            )->post($this->apiUrl.'sendPhoto', [
                'chat_id' => $user->telegram_id,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);

            return;
        }

        // Logo bo'lmasa faqat matn yuborish
        $this->sendMessage($user->telegram_id, strip_tags($caption));
    }

    public function sendMessage(int $chatId, string $message)
    {
        Http::post($this->apiUrl.'sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }
}
