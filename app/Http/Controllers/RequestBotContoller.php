<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RequestBotContoller extends Controller
{
    private string $token;
    private string $apiUrl;
    public function __construct(){
        $this->token = config('services.telegram.incotruck_request_bot');
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    public function webhook(Request $request)
    {
        $updated = $request->all();
        if (!isset($updated['message'])) {
            return response()->json(['status' => 'no message']);
        }
        $chatId = $updated['message']['chat']['id'];
        $text = $updated['message']['text'] ?? '';

        if($text == '/start'){
            $this->sendMessage($chatId, 'Salom');
        }
    }

    public function send(Request $request)
    {   
        $request = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        $name = $request['name'];
        $email = $request['email'];
        $phone = $request['phone'];
        $message = $request['message'];

        $text = "📩 Yangi murojaat\n".
            "🏢 Company: INCOTRUCK\n\n" .
            "👤  Ism: {$name}\n".
            "📧 Email: {$email}\n".
            "📞 Telefon: {$phone}\n".
            "💬 Muammo:\n{$message}";

        // $chatIds = explode(',', env('TELEGRAM_CHAT_IDS'));

        $chatIds = [7510409703,1204315858,6757738816,5275226629];

        foreach ($chatIds as $chatId) {
            Http::post("https://api.telegram.org/bot". '8760336556:AAEcaJLJ-VRbFpB_qSwuESvJIocZ9iRUX4M' ."/sendMessage", [
                'chat_id' => trim($chatId),
                'text' => $text
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent'
        ]);
    }

    public function KGSsend(Request $request)
    {   
        $request = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);

        $firstName = $request['first_name'];
        $lastName = $request['last_name'];
        $email = $request['email'];
        $subject = $request['subject'];
        $message = $request['message'];

       $text = "📩 Yangi murojaat\n"
                . "🏢 Company: KGS\n\n"
                . "👤 Ism Familya: {$firstName} {$lastName}\n"
                . "📧 Email: {$email}\n"
                . "📝 Mavzu: {$subject}\n"
                . "💬 Muammo: {$message}";

        $chatIds = explode(',', env('TELEGRAM_KGS_CHAT_IDS'));

        foreach ($chatIds as $chatId) {
            Http::post("https://api.telegram.org/bot".env('TELEGRAN_KGS_BOT_TOKEN')."/sendMessage", [
                'chat_id' => trim($chatId),
                'text' => $text
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent'
        ]);
    }


    

    private function sendMessage($chatId, $message){
        Http::post('https://api.telegram.org/bot' . $this->token . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }
}
