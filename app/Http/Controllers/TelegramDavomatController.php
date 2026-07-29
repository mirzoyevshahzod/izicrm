<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Imports\AttendanceImport;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TelegramDavomatController extends Controller
{
    private string $token;
    private string $apiUrl;

    // HR chat_id lar
    private array $hrIds = [
        997696865,
        8483752632
        // 6337758881
        // 7510409703
    ];
    public function __construct()
    {
        $this->token  = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Telegram webhook
     */
    public function webhook(Request $request)
    {
        $start = microtime(true);
        Log::info('Webhook start');
        $update = $request->all();

        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        }


        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }

        Log::info('Webhook end', [
            'time' => microtime(true) - $start
        ]);


        return response()->json(['ok' => true]);
    }

    
    /**
     * Message handler
     */
    private function handleMessage(array $message): void
    {
        $chatId    = $message['chat']['id'];
        $firstName = $message['chat']['first_name'] ?? '';
        $lastName  = $message['chat']['last_name'] ?? '';
        $username  = $message['chat']['username'] ?? '';
        $text      = $message['text'] ?? null;

        // 🔵 REGISTER STATE
        $registerState = cache()->get("register_state_{$chatId}");

        if ($registerState === 'waiting_fio' && $text) {

            $parts = preg_split('/\s+/', trim($text));

            if (count($parts) < 2) {
                $this->sendMessage(
                    $chatId,
                    "❌ Iltimos, ism va familiyani to‘liq kiriting.\n\nMasalan: Aliyev Sardor"
                );
                return;
            }

            $firstNameInput = ucfirst(mb_strtolower($parts[1], 'UTF-8'));
            $lastNameInput  = ucfirst(mb_strtolower($parts[0], 'UTF-8'));

            \DB::table('employes')->insert([
                'chat_id'    => $chatId,
                'first_name' => $firstNameInput,
                'last_name'  => $lastNameInput,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            cache()->forget("register_state_{$chatId}");

            $this->sendMessage(
                $chatId,
                "✅ Muvaffaqiyatli ro‘yxatdan o‘tdingiz!\n\n"
                ."👤 {$lastNameInput} {$firstNameInput}"
            );

            return;
        }



       $lateEvent = \DB::table('late_events')
            ->where('chat_id', $chatId)
            ->where('status', 'waiting_reason')
            ->latest()
            ->first();

        if ($lateEvent && $text) {

            \DB::table('late_events')
                ->where('id', $lateEvent->id)
                ->update([
                    'reason' => $text,
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);

            $companyData = $this->getCompanyData($lateEvent->company);

            //bazaga yozamiz
            
            $this->storeAttendance([
                'chat_id'  => $chatId,
                 'company'       => $companyData['company'],
                'director'      => $companyData['director'],
                'fio'           => $this->formatFioShort($lateEvent->fio),
                'day'           => $lateEvent->day,
                'month'         => $lateEvent->month,
                'year'          => $lateEvent->year,
                'late_minutes'  => $lateEvent->late_minutes,
                'reason'        => $text,
            ]);

            $docPath = $this->generateWord([
                'company'       => $companyData['company'],
                'director'      => $companyData['director'],
                'fio'           => $this->formatFioShort($lateEvent->fio),
                'day'           => sprintf('%02d', $lateEvent->day),
                'month'         => sprintf('%02d', $lateEvent->month),
                'year'          => $lateEvent->year,
                'late_minutes'  => $this->minutesToHoursMinutes($lateEvent->late_minutes),
                'reason'        => $text,
            ]);

            // userga yuborish
            Http::attach(
                'document',
                file_get_contents($docPath),
                basename($docPath)
            )->post($this->apiUrl.'/sendDocument', [
                'chat_id' => $chatId,
                'caption' => '📄 Kech qolish bo‘yicha tushuntirish xati',
            ]);

              // userga yuborish
            Http::attach(
                'document',
                file_get_contents($docPath),
                basename($docPath)
            )->post($this->apiUrl.'/sendDocument', [
                'chat_id' => $this->hrIds[0],
                'caption' => "📄 Kech qolish bo‘yicha tushuntirish xati.",
            ]);

            return;
        }


        // ▶️ /start
        if ($text === '/start') {

            // oddiy user bo‘lsa
            if (!in_array($chatId, $this->hrIds)) {

                foreach ($this->hrIds as $hrId) {
                    $this->sendMessage(
                        $hrId,
                        "👤 *Yangi foydalanuvchi botga start bosdi!*\n\n"
                        ."🆔 Chat ID: {$chatId}\n"
                        ."👤 Ism: {$firstName}\n"
                        ."👤 Familiya: {$lastName}\n"
                        ."🔗 Username: @{$username}\n"
                        ."⏰ Sana: ".now()->format('Y-m-d H:i:s')
                    );
                }

                $this->sendMessage(
                    $chatId,
                    "👋 Assalomu alaykum {$firstName} {$lastName}!\n\n"
                    ."Davomat botiga xush kelibsiz.\n"
                    ."Agar kech qolsangiz, bot sizga avtomatik xabar yuboradi."
                );

            } else {
                // HR o‘zi bo‘lsa
                $this->sendMessage(
                    $chatId,
                    "👋 Salom HR {$firstName} {$lastName}!\n\n"
                    ."Davomat botiga xush kelibsiz."
                    ." Mavjud buyruqlar uchun. \n"
                    ."/upload_attendance - Davomat Excel faylini yuklash"
                );
            }

            return;
        }

        if ($text === '/register') {

            // agar oldin ro‘yxatdan o‘tgan bo‘lsa
            $exists = \DB::table('employes')
                ->where('chat_id', $chatId)
                ->exists();

            if ($exists) {
                $this->sendMessage($chatId, "✅ Siz allaqachon ro‘yxatdan o‘tgansiz.");
                return;
            }

            cache()->put("register_state_{$chatId}", 'waiting_fio', 600);

            $this->sendMessage(
                $chatId,
                "📝 Iltimos, ism va familiyangizni kiriting.\n\nMasalan: *Aliyev Sardor*"
            );

            return;
        }

        // ▶️ HR: Employees list + search
        if (str_starts_with($text, '/employees')) {

            if (!in_array($chatId, $this->hrIds)) {
                $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat HR uchun.");
                return;
            }

            // komandani ajratamiz
            $parts = explode(' ', $text, 2);
            $search = $parts[1] ?? null;

            $query = \DB::table('employes');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
                });
            }

            $employees = $query
                ->orderBy('first_name')
                ->get();

            if ($employees->isEmpty()) {
                $this->sendMessage($chatId, "❌ Hech qanday xodim topilmadi.");
                return;
            }

            $message = "👥 *Xodimlar ro‘yxati:*\n\n";

            foreach ($employees as $index => $employee) {
                $message .= ($index + 1) . ". "
                    . $employee->last_name . " "
                    . $employee->first_name
                    . " (ID: {$employee->chat_id})\n";
            }

            $this->sendMessage($chatId, $message);
            return;
        }




        // ▶️ HR: Excel yuklash
        if ($text === '/upload_attendance') {

            if (!in_array($chatId, $this->hrIds)) {
                $this->sendMessage($chatId, "⛔️ Ushbu buyruq faqat HR uchun.");
                return;
            }

            cache()->put("hr_state_{$chatId}", 'waiting_excel', 600);

            $this->sendMessage(
                $chatId,
                "📂 Iltimos, Excel faylni yuboring (.xlsx)\n\n"
                ."❗️Faylda *chat_id* ustuni bo‘lishi shart."
            );
            return;
        }
        

        // ▶️ Excel kutilmoqda (faqat HR)
        $state = cache()->get("hr_state_{$chatId}");

        if (
            $state === 'waiting_excel'
            && isset($message['document'])
            && in_array($chatId, $this->hrIds)
        ) {
            $this->handleExcelUpload($chatId, $message['document']);
        }
    }


    private function handleCallback(array $callback): void
    {
        $chatId = $callback['from']['id'];
        $data   = $callback['data'];

        if ($data === 'write_reason') {

            $lateEvent = \DB::table('late_events')
                ->where('chat_id', $chatId)
                ->where('status', 'waiting_company')
                ->latest()
                ->first();

            if (!$lateEvent) {
                $this->sendMessage($chatId, "❌ Kech qolish ma’lumotlari topilmadi.");
                return;
            }

            $this->showCompanysList($chatId, $callback['message']['message_id']);
            return;
        }


       if (str_starts_with($data, 'company_')) {

            $companyKey = str_replace('company_', '', $data);

            $lateEvent = \DB::table('late_events')
                ->where('chat_id', $chatId)
                ->where('status', 'waiting_company')
                ->latest()
                ->first();

            if (!$lateEvent) {
                $this->sendMessage($chatId, "❌ Kech qolish topilmadi.");
                return;
            }

            \DB::table('late_events')
                ->where('id', $lateEvent->id)
                ->update([
                    'company' => $companyKey,
                    'status'  => 'waiting_reason',
                    'updated_at' => now(),
                ]);

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Bekor qilish', 'cancel']
                        ]
                    ]
                        ];

             Http::post($this->apiUrl . '/editMessageText', [
            'chat_id'    => $chatId,
            'message_id'=> $callback['message']['message_id'],
            'text'       => "✅ Kompaniya tanlandi.\n\n✍️ Endi sababni yozing:",
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '❌ Bekor qilish',
                            'callback_data' => 'cancel_reason'
                        ]
                    ]
                ]
            ]
        ]);
            return;
        }


        if ($data === 'cancel_reason') {

        \DB::table('late_events')
            ->where('chat_id', $chatId)
            ->where('status', 'waiting_reason')
            ->latest()
            ->update([
                'status' => 'waiting_company',
                'updated_at' => now(),
            ]);

        $this->showCompanysList($chatId, $callback['message']['message_id']);
        return;
    }


        // if ($data === 'write_reason') {
        //     cache()->put("awaiting_reason_{$chatId}", true, 600);

        //     Http::post($this->apiUrl . '/editMessageText', [
        //         'chat_id'    => $chatId,
        //         'message_id' => $callback['message']['message_id'],
        //         'text' => 'Iltimos, kech qolish sababini yozib yuboring:',
        //     ]);
        // }
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
                'director' => "Тухтаев Н.К.",
            ],
            'transceka' => [
                'company' => 'TRANSCEKA LOGISTIC SERVICES',
                'director' => 'Абдуюсупов Б.С.',
            ],
            'egs' => [
                'company' => 'EGS group',
                'director' => "Шукуров Р.Н.",
            ],
            default => [
                'company' => 'Noma’lum',
                'director' => '—',
            ],
        };
    }

    private function formatFioShort(string $fio): string
    {
        $parts = preg_split('/\s+/', trim($fio));

        $lastName = ucfirst(mb_strtolower($parts[0] ?? '', 'UTF-8'));
        $firstName = mb_strtolower($parts[1] ?? '', 'UTF-8');

        if (!$firstName) {
            return $lastName;
        }

        // Agar ism "sh" bilan boshlansa → 2 harf olamiz
        if (mb_substr($firstName, 0, 2, 'UTF-8') === 'sh') {
            $initial = ucfirst(mb_substr($firstName, 0, 2, 'UTF-8'));
        } else {
            $initial = ucfirst(mb_substr($firstName, 0, 1, 'UTF-8'));
        }

        return "{$lastName} {$initial}";
    }




    private function showCompanysList(int $chatId, int $messageId): void
    {
        $keyboard = [
            [
                ['text' => 'Izisol', 'callback_data' => 'company_izisol'],
                ['text' => 'Express', 'callback_data' => 'company_eastline'],
            ],
            [
                ['text' => 'Incotruck', 'callback_data' => 'company_incotruck'],
                ['text' => 'Transceka', 'callback_data' => 'company_transceka'],
            ],
            [
                ['text' => 'EGS', 'callback_data' => 'company_egs'],
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

    private function storeAttendance(array $data)
    {
        // Ma'lumotlarni bazaga saqlash
        $attendance = \DB::table('attendances')->insert([
            'chat_id'      => $data['chat_id'],
            'fio'          => $data['fio'],
            'company'      => $data['company'],
            'day'          => $data['day'],
            'month'        => $data['month'],
            'year'         => $data['year'],
            'reason'       => $data['reason'] ?? null,
            'late_minutes' => $data['late_minutes'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $attendance;
    }
   

    /**
     * Excel upload va qayta ishlash
     */
   private function handleExcelUpload(int $chatId, array $document): void
    {
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
        $localPath = 'excel/attendance_' . now()->format('Ymd_His') . '.xlsx';
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

        /**
         * Excel’da:
         * 1-qator: title
         * 2-qator: bo‘sh
         * 3-qator: header
         */
        array_shift($rows);
        array_shift($rows);
        array_shift($rows);

        $lateCount   = 0;
        $onTimeCount = 0;
        $lateChatIds = []; 
        foreach ($rows as $row) {

            // Excel indexlari (0-based)
         $name = trim($row[1] ?? '');
        $date = trim($row[3] ?? '');
        $time = trim($row[4] ?? '');
        $userChatId = trim($row[5] ?? '');


        if ($name === '' || $date === '' || $time === '' || $userChatId === '') {
        continue;
        }


        try {

            // Agar time Excel numeric bo‘lsa (0.3875 kabi)
            if (is_numeric($time)) {

                $timeCarbon = Carbon::instance(
                    ExcelDate::excelToDateTimeObject($time)
                );

                $dateCarbon = Carbon::createFromFormat('d.m.Y', $date);

                $arrival = Carbon::create(
                    $dateCarbon->year,
                    $dateCarbon->month,
                    $dateCarbon->day,
                    $timeCarbon->hour,
                    $timeCarbon->minute
                );

            } 
            // Agar time oddiy string bo‘lsa (09:15)
            else {

                $arrival = Carbon::createFromFormat(
                    'd.m.Y H:i',
                    $date . ' ' . $time
                );
            }

        } catch (\Exception $e) {

            Log::error('DATE PARSE ERROR', [
                'value'   => $date.' '.$time,
                'chat_id'=> $userChatId
            ]);

            continue;
        }

        $workStart = Carbon::create(
        $arrival->year,
        $arrival->month,
        $arrival->day,
        9, 0, 0
        );


        $lateMinutes = $workStart->diffInMinutes($arrival, false);

            if ($lateMinutes > 0) {
                // 🔴 KECH
                $lateCount++;
                $lateChatIds[] = (int)$userChatId; 

                \DB::table('late_events')->updateOrInsert(
                    [
                        'chat_id' => $userChatId,
                        'day'     => $arrival->day,
                        'month'   => $arrival->month,
                        'year'    => $arrival->year,
                    ],
                    [
                        'fio'          => $name,
                        'late_minutes' => $lateMinutes,
                        'status'       => 'waiting_company',
                        'updated_at'   => now(),
                        'created_at'   => now(),
                    ]
                );


                $this->sendMessageWithButtons(
                    (int)$userChatId,
                    "⏰ Assalomu alaykum, {$name}.\n\n"
                    ."Bugun ishga *{$lateMinutes} daqiqa* kech keldingiz.\n"
                    ."Iltimos, kech qolish sababini quyidagi tugma orqali yozib yuboring.",
                    'write_reason'
                );
            
            } 
        }

       // 🔵 Excelda yo‘q bo‘lganlarga rahmat yuborish

        $allEmployees = \DB::table('employes')->pluck('chat_id')->toArray();

        // Excelda kech qolganlardan tashqari qolganlar
        $onTimeEmployees = array_diff($allEmployees, $lateChatIds);

        foreach ($onTimeEmployees as $employeeChatId) {

            try {
                $employee = \DB::table('employes')
                    ->where('chat_id', $employeeChatId)
                    ->first();

                if ($employee) {
                    $this->sendMessage(
                        (int)$employeeChatId,
                        $this->getRandomOnTimeMessage($employee->first_name)
                    );
                }

            } catch (\Exception $e) {
                Log::warning('ON TIME MESSAGE FAILED', [
                    'chat_id' => $employeeChatId
                ]);
            }
        }


        cache()->forget("hr_state_{$chatId}");

        // 5️⃣ HRga yakuniy hisobot
        $this->sendMessage(
            $chatId,
            "📊 *Davomat hisoboti tayyor!*\n\n"
            ."⏰ Kech qolganlar: {$lateCount}\n"
            ."✅ O‘z vaqtida kelganlar: {$onTimeCount}"
        );
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
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $file = $dir.'/explanation_'.time().'.docx';
        $template->saveAs($file);

        return $file;
    }

    private function minutesToHoursMinutes(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return "{$hours} час {$minutes} минут";   
        }

        return "{$minutes} минут";
    }

     /**
     * Telegram message yuborish tugmali
     */


    private function sendMessageWithButtons(int $chatId, string $text, string $callbackData): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [
                    [
                        [
                            'text'          => 'Sababini yozish',
                            'callback_data' => $callbackData,
                        ],
                    ],
                ],
            ],
        ]);
    }



    /**
     * Telegram message yuborish
     */
    private function sendMessage(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id' => $chatId,
            'text'    => $text,
        ]);
    }

    private function getRandomOnTimeMessage(string $name): string
    {
       $messages = [

            // 🇺🇿 O'zbekcha – kulgili variantlar 😄

            "😎 {$name}, soat sizdan vaqt so‘raydigan darajaga yetdi!",
            "⏰ {$name}, signal ham sizdan oldin uyg‘onishga uyalyapti!",
            "😂 {$name}, bugun ham kech qolishga imkon bermadingiz!",
            "🚀 {$name}, teleport qilgandek vaqtida yetib keldingiz!",
            "🔥 {$name}, ishxonaga VIP kirish – yana vaqtida!",
            "🏃 {$name}, shamoldan tez, soatdan aniq!",
            "🎉 {$name}, bugun ham bahona topolmadi kechikish!",
            "🫡 {$name}, vaqt sizni hurmat qiladi!",
            "🥷 {$name}, jim kelib, rekord qo‘ydingiz!",
            "💪 {$name}, bosslar ham sizdan o‘rnak olsa bo‘ladi!",
            "📅 {$name}, kalendar ham sizni kutib turadi!",
            "😄 {$name}, bugun ham ‘kech qoldim’ degan gap ishlamadi!",
            "🚦 {$name}, svetoforlar ham sizga yashil yonadi!",
            "🏆 {$name}, ‘Punktuallik vaziri’ lavozimi sizniki!",
            "😆 {$name}, Google Maps ham sizdan maslahat oladi!",
            "⚡ {$name}, tezlik va intizom – combo!",
            "🎯 {$name}, snayperdek aniq vaqtida!",
            "😇 {$name}, bugun ham farishtadek vaqtida tushdingiz!",
            "🕶 {$name}, vaqtida kelish – sizning superqobiliyatingiz!",
            "🤣 {$name}, kechikish sizdan qo‘rqadi!",


            // 🇷🇺 Русский – смешные варианты 😄

            "😎 {$name}, даже часы под вас подстраиваются!",
            "⏰ {$name}, будильник гордится вами!",
            "😂 {$name}, опоздание снова проиграло!",
            "🚀 {$name}, как будто телепортировались на работу!",
            "🔥 {$name}, пунктуальность уровня VIP!",
            "🏃 {$name}, быстрее ветра, точнее секунд!",
            "😄 {$name}, excuses.exe не запустился!",
            "🫡 {$name}, время вас уважает!",
            "🥷 {$name}, тихо пришли — рекорд поставили!",
            "💪 {$name}, дисциплина 100 lvl!",
            "📅 {$name}, календарь вами доволен!",
            "🤣 {$name}, опоздание вас боится!",
            "🚦 {$name}, все светофоры зелёные для вас!",
            "🏆 {$name}, министр пунктуальности!",
            "😆 {$name}, даже Google Maps у вас учится!",
            "⚡ {$name}, скорость + дисциплина = успех!",
            "🎯 {$name}, точность как у снайпера!",
            "😇 {$name}, идеальное начало дня!",
            "🕶 {$name}, суперспособность — приходить вовремя!",
            "🎉 {$name}, снова 100% вовремя — браво!",

        ];


        return $messages[array_rand($messages)];
    }

}
