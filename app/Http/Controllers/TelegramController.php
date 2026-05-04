<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramController extends Controller
{
    private $token;

    public function __construct()
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
    }

    private function sendMessage($chat_id, $text, $keyboard = null)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        $data = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }

        Http::post($url, $data);
    }

    private function getFile($file_id)
    {
        $url = "https://api.telegram.org/bot{$this->token}/getFile?file_id={$file_id}";
        return Http::get($url)->json();
    }

    private function downloadFile($path)
    {
        $url = "https://api.telegram.org/file/bot{$this->token}/$path";
        return file_get_contents($url);
    }

    public function webhook(Request $request)
    {
        $update = $request->all();

        $message = $update['message'] ?? null;

        if (!$message) {
            return response('ok', 200);
        }

        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? null;
        $contact = $message['contact']['phone_number'] ?? null;
        $document = $message['document']['file_id'] ?? null;
        $photo = null;

       if (isset($message['photo'])) {
            $photos = $message['photo'];
            $photo = end($photos)['file_id'];  // foto eng katta versiyasini olish
        }

        $document = $message['document']['file_id'] ?? null;
        $file_id = $document ?? $photo;

        // STEP olish yoki yaratish
        $step = DriverStep::firstOrCreate(
            ['chat_id' => $chat_id],
            ['step' => 'start']
        );

        // START
        if ($text === '/start') {
            $step->update(['step' => 'ask_phone']);

            $keyboard = [
                "keyboard" => [
                    [
                        ["text" => "📞 Telefon raqamni ulashish", "request_contact" => true]
                    ]
                ],
                "resize_keyboard" => true,
                "one_time_keyboard" => true
            ];

            $this->sendMessage($chat_id, "📞 Telefon raqamingizni yuboring", $keyboard);
            return response('ok', 200);
        }

        // 1) PHONE NUMBER
        // 1) PHONE NUMBER
        if ($step->step === 'ask_phone') {

            // Telefon raqam contact orqali keldi
            if ($contact) {
                Driver::updateOrCreate(
                    ['chat_id' => $chat_id],
                    ['phone' => $contact]
                );

                $step->update(['step' => 'ask_cmr']);

                $removeKeyboard = ["remove_keyboard" => true];
                $this->sendMessage($chat_id, "📄 CMR hujjatni yuboring (PDF yoki rasm).", $removeKeyboard);
                return response('ok', 200);
            }

            // Foydalanuvchi matn yubordi
            if ($text && preg_match('/^\+?\d{9,15}$/', $text)) { // oddiy regex
                Driver::updateOrCreate(
                    ['chat_id' => $chat_id],
                    ['phone' => $text]
                );

                $step->update(['step' => 'ask_cmr']);

                $removeKeyboard = ["remove_keyboard" => true];
                $this->sendMessage($chat_id, "📄 CMR hujjatni yuboring (PDF yoki rasm).", $removeKeyboard);
                return response('ok', 200);
            }

            // Telefon raqam kelmagan bo‘lsa
            $this->sendMessage($chat_id, "📞 Iltimos telefon raqamni ulashing.");
            return response('ok', 200);
        }


        // 2) CMR FILE (PDF yoki rasm)
        if ($step->step === 'ask_cmr') {

            $file_id = $document ?? $photo;

            // ? Fayl yubormagan bo'lsa majbur qilish
            if (!$file_id) {
                $this->sendMessage($chat_id, "📄 Siz fayl yubormadingiz. Iltimos CMR hujjatini PDF yoki rasm ko�rinishida yuboring.");
                return response('ok', 200);
            }

            $fileInfo = $this->getFile($file_id);
            Log::info('Result', ['file_id' => $fileInfo]);

            if (!isset($fileInfo['result']['file_path'])) {
                $this->sendMessage($chat_id, "📄 Faylni yuklab bo�lmadi. Qaytadan yuboring.");
                return response('ok', 200);
            }

            // Faylni yuklash
            $filePath = $this->downloadFile($fileInfo['result']['file_path']);
            $savedPath = "cmr/" . time() . "_" . basename($fileInfo['result']['file_path']);

            Storage::disk('public')->put($savedPath, $filePath);

            // Driverni topish
            $driver = Driver::where('chat_id', $chat_id)->first();


            // Yangi faylni driver_files ga yozish
            if ($driver) {
                $driver->files()->create([
                'file_path' => $savedPath
                ]);
            }



            $step->update(['step' => 'ask_destination']);

            $this->sendMessage(
                $chat_id,
                "📍 Qayerga ketyapsiz?\n\nDavlat va shaharni yuboring.\nMasalan:\nTurkiya, Istanbul"
            );
            return response('ok', 200);
        }

        // 3) DESTINATION
        if ($step->step === 'ask_destination' && $text) {

            [$country, $region] = array_pad(explode(',', $text), 2, null);

            Driver::updateOrCreate(
                ['chat_id' => $chat_id],
                [
                    'destination_country' => trim($country),
                    'destination_region' => trim($region)
                ]
            );

            $step->update(['step' => 'done']);

            $this->sendMessage($chat_id, "✅ Rahmat! Barcha ma'lumotlar saqlandi.");
            return response('ok', 200);
        }

        // Default fallback
        $this->sendMessage($chat_id, "? Iltimos /start buyrug�idan boshlang.");
        return response('ok', 200);
    }
}
