<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Imports\AttendanceImport;
use Maatwebsite\Excel\Facades\Excel;


class DebtController extends Controller
{
    private string $apiUrl;
    private string $token;

    private $drIds = [
        // 7510409703,
        887162370
        // 6337758881

    ];

    public function __construct(){
        $this->token = config('services.telegram.bot_token3');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function webhook(Request $request){
        Log::info('Telegram webhook HIT', $request->all());
        $update = $request->all();

        $message = $update['message'] ?? null;

        if(isset($message)){
            $this->handleMessage($message);
            return response()->json(['ok' => true]);
        }

        if (isset($update['callback_query'])) {
        $this->handleCallback($update['callback_query']);
        return response()->json(['ok' => true]);
    }



        return response()->json(['ok' => true]);

    }

    private function handleMessage($message){

        $text = $message['text'] ?? '';
        $firstname = $message['chat']['first_name'] ?? '';
        $lastname = $message['chat']['last_name'] ?? '';
        $username = $message['from']['user_name'] ?? '';
        $chatId = $message['chat']['id'];


        if($text === '/start'){
            if(!in_array($chatId, $this->drIds)){
                $text = "👋 Assalomu alaykum, {$lastname} {$firstname}!\n\n".
                    "📌 Sizga kompaniyalar bo‘yicha qarzdorliklar yuboriladi.\n\n".
                    "✍️ Har bir qarzdorlik bo‘yicha sabab yozishingiz kerak.\n".
                    "Siz:\n".
                    "• Matn yuborishingiz mumkin\n".
                    "• 🎤 Ovozli xabar yuborishingiz mumkin\n".
                    "• 📷 Rasm yuborishingiz mumkin\n\n".
                    "⏳ Iltimos, botdan keladigan xabarlarni kuting.";

                $this->sendMessage($chatId, $text);
            }else{
                $text = "👋 Assalomu alaykum, {$lastname} {$firstname}!\n\n".
                    "🧑‍💼 Siz *mas’ul shaxs* sifatida tizimdasiz.\n\n".
                    "📊 Qarzdorliklar Excel faylini yuboring.\n\n".
                    "⚠️ Talablar:\n".
                    "• Fayl .xlsx bo‘lsin\n".
                    "• Oxirgi ustun `chat_id` bo‘lsin\n".
                    "• Har bir xodimning Telegram chat_id si yozilgan bo‘lsin".
                    "• Har bir xodimning javoblarini https://izicrm.uz/debt-login shu saytda korinadi.\n\n" .
                    "• Exel faylni /upload_debt buyruqdan keyin\n".
                    "📎 Excel faylni shu yerga yuboring.";

                $this->sendMessage($chatId, $text);
            }
            return;
        }

        // ❗ HR bo‘lsa, lekin /upload_debt bermay turib boshqa xabar yuborsa
        if (
            in_array($chatId, $this->drIds) // HR
            && !in_array($text, ['/start', '/upload_debt']) // buyruq emas
        ) {
            $state = cache()->get("dr_state_{$chatId}");

            // Agar Excel kutilmayotgan bo‘lsa
            if ($state !== 'waiting_excel') {
                $this->sendMessage(
                    $chatId,
                    "❗️Kechirasiz, avval *Excel fayl yuborish* uchun\n\n".
                    "👉 /upload_debt\n\n".
                    "buyrug‘ini yuboring."
                );
                return;
            }
        }

        if ($text === '/docs') {

            $videoPath = storage_path('app/docs/user_guide.mp4');

            if (!file_exists($videoPath)) {
                $this->sendMessage($chatId, "❌ Video qo‘llanma topilmadi.");
                return;
            }

            Http::attach(
                'video',
                file_get_contents($videoPath),
                'user_guide.mp4'
            )->post($this->apiUrl.'/sendVideo', [
                'chat_id' => $chatId,
                'caption' =>
                    "🎥 *Botdan foydalanish bo‘yicha video qo‘llanma*\n\n".
                    "Agar savollar bo‘lsa, mas’ul shaxsga murojaat qiling.",
                'parse_mode' => 'Markdown'
            ]);

            return;
        }




        if($text === '/upload_debt'){
             if (!in_array($chatId, $this->drIds)) {
                $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat mas’ul xodim uchun uchun.");
                return;
            }

            cache()->put("dr_state_{$chatId}", 'waiting_excel', 600);

            $this->sendMessage(
                $chatId,
                "📂 Iltimos, Excel faylni yuboring (.xlsx)\n\n"
                ."❗️Faylda *chat_id* ustuni bo‘lishi shart."
            );
            return;
        
        }

        $state = cache()->get("dr_state_{$chatId}");
        if($state === 'waiting_excel' && isset($message['document']) && in_array($chatId, $this->drIds)){
            $this->handleExcelUpload($chatId, $message['document']);
            cache()->forget("dr_state_{$chatId}");
        }

        if (in_array($chatId, $this->drIds)) {
            return;
        }


       $state = DB::table('user_states')
    ->where('chat_id', $chatId)
    ->first();

    if ($state && in_array($state->status, ['writing_reason', 'waiting_photo', 'waiting_voice'])) {
    if (str_starts_with($text, '/')) {
        return;
    }

    $reasonSaved = false;
    
        // 1️⃣ Matn
        if (!empty($text)) {
            DB::table('debt_reasons')->insert([
                'debt_id' => $state->debt_id,
                'period' => $state->period,
                'chat_id' => $chatId,
                'type' => 'text',
                'message_text' => $text,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2️⃣ Ovoz
       if (isset($message['voice'])) {

            $path = $this->saveTelegramFile($message['voice']['file_id'], 'voices');

            DB::table('debt_reasons')->insert([
                'debt_id' => $state->debt_id,
                'period' => $state->period,
                'chat_id' => $chatId,
                'type' => 'voice',
                'file_path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (in_array($state->status, ['waiting_photo', 'waiting_voice'])) {

                DB::table('user_states')
                    ->where('chat_id', $chatId)
                    ->update([
                        'status' => 'done',
                        'updated_at' => now()
                    ]);

                Http::post($this->apiUrl.'/editMessageText', [
                    'chat_id' => $chatId,
                    'message_id' => $state->message_id,
                    'text' => "✅ Ma’lumotlar saqlandi. Rahmat!",
                    'reply_markup' => json_encode(['inline_keyboard' => []])
                ]);

                return;
            }

            DB::table('user_states')
                ->where('chat_id', $chatId)
                ->update([
                    'status' => 'waiting_next',
                    'updated_at' => now()
                ]);

            $this->askContinue($chatId);
            return;
        }



        // 3️⃣ Rasm
        if (isset($message['photo'])) {

            $photo = end($message['photo']);
            $path = $this->saveTelegramFile($photo['file_id'], 'photos');

            DB::table('debt_reasons')->insert([
                'debt_id' => $state->debt_id,
                'period' => $state->period,
                'chat_id' => $chatId,
                'type' => 'photo',
                'file_path' => $path,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 🔑 AGAR finish jarayonida bo‘lsa → tugatamiz
            if (in_array($state->status, ['waiting_photo', 'waiting_voice'])) {

                DB::table('user_states')
                    ->where('chat_id', $chatId)
                    ->update([
                        'status' => 'done',
                        'updated_at' => now()
                    ]);

                Http::post($this->apiUrl.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => "✅ Ma’lumotlar saqlandi. Rahmat!",
                ]);

                return;
            }

            // 🔁 AKS HOLDA → YANA SO‘RAYMIZ
            DB::table('user_states')
                ->where('chat_id', $chatId)
                ->update([
                    'status' => 'waiting_next',
                    'updated_at' => now()
                ]);

            $this->askContinue($chatId);
            return;
        }


         if ($reasonSaved) {
        DB::table('user_states')
            ->where('chat_id', $chatId)
            ->update([
                'status' => 'waiting_next',
                'updated_at' => now()
            ]);

        $this->askContinue($chatId);
    }

        // 🔁 STATUS O‘ZGARTIRAMIZ
        DB::table('user_states')
            ->where('chat_id', $chatId)
            ->update([
                'status' => 'waiting_next',
                'updated_at' => now()
            ]);

        // 4️⃣ So‘rov tugmalari
        $this->askContinue($chatId);
        return;
    }

    }

    public function handleCallback(array $callback)
    {
        $chatId = $callback['from']['id'];
        $data = explode('|', $callback['data']);

        // 1️⃣ SABAB TANLANDI
        if ($data[0] === 'reason') {
            [$action, $type, $debtId] = $data;

            DB::table('user_states')->updateOrInsert(
                ['chat_id' => $chatId],
                [
                    'debt_id' => $debtId,
                    'period' => 'general',
                    'status' => 'writing_reason',
                    'updated_at' => now()
                ]
            );

            Http::post($this->apiUrl.'/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text' =>
                    "✍️ Qarzdorlik bo‘yicha sabab yuboring.\n\n".
                    "Matn, 🎤 ovoz yoki 📷 rasm yuborishingiz mumkin.",
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);
        }



        // 2️⃣ YANA SABAB YOZAMAN
          if ($data[0] === 'stay') {

            $messageId = $callback['message']['message_id'];

            DB::table('user_states')
                ->where('chat_id', $chatId)
                ->update([
                    'status' => 'writing_reason',
                    'updated_at' => now()
                ]);

            Http::post($this->apiUrl.'/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' =>
                    "✍️ Qarzdorlik bo‘yicha yana sabab yuboring.\n\n".
                    "Matn, 🎤 ovoz yoki 📷 rasm yuborishingiz mumkin.",
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);

            return;
        }

        if ($data[0] === 'send_photo') {

            DB::table('user_states')
                ->where('chat_id', $chatId)
                ->update([
                    'status' => 'waiting_photo',
                    'updated_at' => now()
                ]);

            Http::post($this->apiUrl.'/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text' => "📷 Iltimos, hozir rasm (screenshot) yuboring.",
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);

            return;
        }


        if ($data[0] === 'send_voice') {

            DB::table('user_states')
                ->where('chat_id', $chatId)
                ->update([
                    'status' => 'waiting_voice',
                    'updated_at' => now()
                ]);

            Http::post($this->apiUrl.'/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text' => "🎤 Iltimos, hozir ovozli xabar yuboring.",
                'reply_markup' => json_encode(['inline_keyboard' => []])
            ]);

            return;
        }


       if ($data[0] === 'finish') {

        $state = DB::table('user_states')
            ->where('chat_id', $chatId)
            ->first();

        // 🔎 Shu debt bo‘yicha dalil bormi?
        $hasEvidence = DB::table('debt_reasons')
            ->where('chat_id', $chatId)
            ->where('debt_id', $state->debt_id)
            ->whereIn('type', ['voice', 'photo'])
            ->exists();

        // ❌ Dalil YO‘Q bo‘lsa
        if (!$hasEvidence) {

            Http::post($this->apiUrl.'/editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $callback['message']['message_id'],
                'text' =>
                    "❗️ Iltimos, sababni dalil bilan tasdiqlang.\n".
                    "📷 Rasm yoki 🎤 ovozli xabar yuboring.",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => '📷 Rasm yuborish', 'callback_data' => 'send_photo'],
                            ['text' => '🎤 Ovozli xabar yuborish', 'callback_data' => 'send_voice']
                        ]
                    ]
                ])
            ]);

            return;
        }

        DB::table('user_debt_queues')
            ->where('chat_id', $chatId)
            ->increment('current_index');

        $this->sendNextDebt($chatId);


        // ✅ Dalil BOR bo‘lsa — tugatamiz
        DB::table('user_states')
            ->where('chat_id', $chatId)
            ->update([
                'status' => 'done',
                'updated_at' => now()
            ]);

        Http::post($this->apiUrl.'/editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $callback['message']['message_id'],
            'text' => "✅ Sabablar qabul qilindi. Rahmat!",
            'reply_markup' => json_encode(['inline_keyboard' => []])
        ]);

        return;
    }



}



    private function handleExcelUpload($chatId, $document){
        // 1️⃣ faqat xlsx
        $ext = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));

       if(in_array($ext, ['xls', 'xlsx', 'xltx']) === false) {
            $this->sendMessage($chatId, "❌ Iltimos, faqat .xls yoki .xlsx formatdagi faylni yuboring.");
            return;
        }
        
         // 2️⃣ Telegram file path
        $fileInfo = Http::get($this->apiUrl . '/getFile', [
            'file_id' => $document['file_id']
        ])->json();

          if (!isset($fileInfo['result']['file_path'])) {
            $this->sendMessage($chatId, "❌ Faylni olishda xatolik.");
            return;
        }

         $fileUrl = "https://api.telegram.org/file/bot{$this->token}/{$fileInfo['result']['file_path']}";
        $content = Http::get($fileUrl)->body();

        if (!$content) {
            $this->sendMessage($chatId, "❌ Fayl yuklab olinmadi.");
            return;
        }

          // 3️⃣ storage/app/excel ga saqlash
        $localPath = 'debt_excel/debt_' . now()->format('Ymd_His') . '.' . $ext;
        Storage::disk('local')->put($localPath, $content);

        if (!Storage::disk('local')->exists($localPath)) {
            $this->sendMessage($chatId, "❌ Serverga fayl yozilmadi.");
            return;
        }

         // 4️⃣ Excel o‘qish
        $rows = Excel::toArray(
            new AttendanceImport(),
            $localPath,
            'local'
        )[0];

        array_shift($rows); // sarlavha

        $debtsByUser = [];
        $debtCount = 0;
        foreach ($rows as $row) {

            $company    = trim($row[2] ?? '');
            $total      = trim($row[3] ?? '');
            $employee   = trim($row[4] ?? '');
            $userChatId = trim($row[5] ?? '');

            if (!is_numeric($userChatId) || $company === '' || $total === '') {
                continue;
            }

            $debtId = $this->storeDebt([
                'company' => $company,
                'employee' => $employee,
                'total' => $total,
                'userChatId' => $userChatId,
            ]);
            $debtCount ++;

            $debtsByUser[$userChatId][] = $debtId;
        }

        // 🔥 HAR BIR XODIM UCHUN NAVBAT (QUEUE)
        foreach ($debtsByUser as $chatId => $debtIds) {

            DB::table('user_debt_queues')->updateOrInsert(
                ['chat_id' => $chatId],
                [
                    'debt_ids' => json_encode($debtIds),
                    'current_index' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 👉 faqat 1-qarzni yuboramiz
            $this->sendNextDebt($chatId);
        }


    $report =
        "🎉 *Excel muvaffaqiyatli yuklandi!*\n\n".
        "👀 *Xodimlar javoblarini shu yerda kuzating:*\n".
        "🔗 https://izicrm.uz/debt-login\n\n".
        "📨 Xabarlar yuborildi: {$debtCount} ta\n".
        "📄 Excel qatorlari: ".count($rows)." ta\n\n".
        "🕒 Sana: ".now()->format('d.m.Y H:i');

    $this->sendMessage($this->drIds[0], $report);


    }

    private function sendNextDebt($chatId)
    {
        $queue = DB::table('user_debt_queues')
            ->where('chat_id', $chatId)
            ->first();

        if (!$queue) return;

        $debtIds = json_decode($queue->debt_ids, true);
        $index = $queue->current_index;

        // ❌ Qarzlar tugagan bo‘lsa
        if (!isset($debtIds[$index])) {
            $this->sendMessage($chatId,
                "🎉 Barcha qarzdorliklar bo‘yicha sabablar qabul qilindi!\nRahmat 🙏"
            );
            return;
        }

        $debtId = $debtIds[$index];
        $debt = DB::table('debts')->where('id', $debtId)->first();

        $this->showDebtStep($chatId, $debt, $index + 1, count($debtIds));
    }

    private function showDebtStep($chatId, $debt, $current, $total)
    {
        $response = Http::post($this->apiUrl.'/sendMessage', [
            'chat_id' => $chatId,
            'text' =>
                "🧾 Qarzdorlik {$current} / {$total}\n\n".
                "👤 Xodim: {$debt->employee_name}\n".
                "🏢 Kompaniya: {$debt->company_name}\n".
                "💰 Qarzdorlik: {$debt->total_amount} so‘m\n\n".
                "✍️ Iltimos, aynan SHU qarz bo‘yicha sabab yozing",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✍️ Sabab yozish',
                            'callback_data' => "reason|general|{$debt->id}"
                        ]
                    ]
                ]
            ])
        ])->json();

        DB::table('user_states')->updateOrInsert(
            ['chat_id' => $chatId],
            [
                'debt_id' => $debt->id,
                'message_id' => $response['result']['message_id'],
                'status' => 'writing_reason',
                'updated_at' => now()
            ]
        );
    }



    private function showDebtBtn(array $data)
    {
        $response = Http::post($this->apiUrl.'/sendMessage', [
            'chat_id' => (int)$data['chat_id'],
            'text' =>
                "👤 Mas’ul: {$data['employee']}\n".
                "🏢 Kompaniya: {$data['company']}\n\n".
                "⚠️ Quyidagi qarz bo‘yicha sabab kiriting",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✍️ Sabab yozish',
                            'callback_data' => "reason|general|{$data['debt_id']}"
                        ]
                    ]
                ]
            ])
        ])->json();

        DB::table('user_states')->updateOrInsert(
            ['chat_id' => $data['chat_id']],
            [
                'debt_id' => $data['debt_id'],
                'message_id' => $response['result']['message_id'],
                'status' => 'writing_reason',
                'updated_at' => now()
            ]
        );
    }

    private function storeDebt(array $data): int
    {
        return DB::table('debts')->insertGetId([
            'company_name' => $data['company'],
            'employee_name' => $data['employee'],
            'total_amount' => $data['total'],
            'chat_id' => $data['userChatId'],

            // periodlar 0 bo‘lib qoladi
            'day_0_7' => 0,
            'day_8_15' => 0,
            'day_16_30' => 0,
            'day_31_60' => 0,
            'day_61_90' => 0,
            'day_90_plus' => 0,

            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }



    private function askContinue($chatId)
    {
        $response = Http::post($this->apiUrl.'/sendMessage', [
            'chat_id' => $chatId,
            'text' => "✅ Sabab saqlandi.\n\nYana sabab yozasizmi?",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '➕ Yana sabab yozaman', 'callback_data' => 'stay'],
                        ['text' => '✅ Tugatish', 'callback_data' => 'finish']
                    ]
                ]
            ])
        ])->json();

        DB::table('user_states')
            ->where('chat_id', $chatId)
            ->update([
                'message_id' => $response['result']['message_id'],
                'updated_at' => now()
            ]);
    }




    private function saveTelegramFile($fileId, $folder)
    {
        $file = Http::get($this->apiUrl.'/getFile', ['file_id' => $fileId])->json();
        $path = $file['result']['file_path'];

        $content = Http::get("https://api.telegram.org/file/bot{$this->token}/{$path}")->body();
        $localPath = "reasons/{$folder}/".uniqid()."_".basename($path);

        Storage::disk('public')->put($localPath, $content);

        return $localPath;
    }



    private function sendMessage(int $chatId, string $text){
        
    Http::post($this->apiUrl . "/sendMessage", [
          'chat_id' => $chatId,
            'text'    => $text,
    ]);

    }


}
