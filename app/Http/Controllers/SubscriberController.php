<?php


namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SubscriberController extends Controller
{
    private $api;

    public function __construct()
    {
        $this->api = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN1') . "/";
    }

    public function webhook(Request $request)
{
    $update = $request->all();

    // 🟢 1) message yoki channel_post qabul qilish
    $msg = $update['message'] ?? $update['channel_post'] ?? null;

    if (!$msg) {
        return response('ok');
    }

    $chatId = $msg['chat']['id'] ?? null;
    $text   = $msg['text'] ?? null;
    $chatType = $msg['chat']['type'] ?? null;

    if (!$chatId) {
        return response('ok');
    }

    // 🟢 2) START — faqat private chatda ishlasin (guruhda emas)
    if ($text == "/start" && $chatType == 'private') {
        Subscriber::firstOrCreate(['chat_id' => $chatId]);
        $this->sendText($chatId, "Botga xush kelibsiz! Bazalarni sizga yuboraman.");
        return response('ok');
    }

    // 🟢 3) Matn bo‘lsa guruhdan yubormaymiz (faqat private matn qabul qilinadi)
    if ($chatType != 'private' && !$this->isFileMessage($msg)) {
        return response('ok');
    }

    // 🟢 4) Faqat fayllar broadcast qilinadi
    if ($this->isFileMessage($msg)) {
        $this->broadcastFile($msg);
    }

    return response('ok');
}


    // 🔍 Fayl borligini tekshirish
    private function isFileMessage($msg)
    {
        return isset($msg['document']);
    }



    private function broadcastFile($msg)
    {
        $chatType = $msg['chat']['type'];
        $fromChatId = $msg['chat']['id'];

        // 🔹 Guruhdan kelgan fayllar uchun
        $GROUP_RECEIVERS = [7510409703, 1056304469];

        // 🔹 Botga private tashlangan fayllar uchun
        $PRIVATE_RECEIVERS = [75714317,6757738816,7510409703,1056304469];
        
        $ALLOWED_SENDER = 7510409703;
        $ALLOWED_GROUP_SENDER = -1003062759085;

        if ($fromChatId != $ALLOWED_SENDER && $fromChatId != $ALLOWED_GROUP_SENDER) {
            return; // boshqa odam yuborsa hech narsa qilmaydi
        }

        if ($chatType == 'group' || $chatType == 'supergroup') {

            foreach ($GROUP_RECEIVERS as $receiver) {

                if ($receiver == $fromChatId) continue;

                $this->sendFile($receiver, $msg);
            }

        } elseif ($chatType == 'private') {

            foreach ($PRIVATE_RECEIVERS as $receiver) {

                if ($receiver == $fromChatId) continue;

                $this->sendFile($receiver, $msg);
            }
        }
    }


    // 🔁 Forward qilish
    private function forward($to, $from, $msgId)
    {
        Http::post($this->api . "forwardMessage", [
            'chat_id'      => $to,
            'from_chat_id' => $from,
            'message_id'   => $msgId,
        ]);
    }
     public function sendText($chatId, $text, $parseMode = 'HTML')
    {
        return Http::post($this->api . 'sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ])->json();
    }

    private function sendFile($chatId, $msg)
{
    // Document
    if (isset($msg['document'])) {
        Http::post($this->api . "sendDocument", [
            'chat_id' => $chatId,
            'document' => $msg['document']['file_id'],
            // 'caption' => $msg['caption'] ?? null
        ]);
    }


}

}
