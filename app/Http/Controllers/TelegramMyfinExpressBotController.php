<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramMyfinExpressBotController extends Controller
{
    protected $telegram;
    private $allowedUserChatIds = [6051881564, 6757738816, 7949626123]; // Ruxsat berilgan foydalanuvchi chat ID
    private $targetGroupChatId = -1002211786641; // Xabar yuboriladigan guruh chat ID

    public function __construct()
    {
        $this->telegram = new Api('8057512942:AAHmjhKpfk5jnn7_aEHU4_K2j2HTI9yL_pw');
    }

    public function webhook()
    {
        try {
            $update = $this->telegram->getWebhookUpdate();
            Log::info('Update received: ' . json_encode($update)); // Yangilanish logi

            if ($update->getMessage()) {
                $message = $update->getMessage();
                $chatId = $message->getChat()->getId();
                Log::info('Message received from chat ID: ' . $chatId); // Xabar kelgan chat ID

                if (in_array($chatId, $this->allowedUserChatIds)) {
                    // /start komandasiga javob
                    if ($message->getText() === '/start') {
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => "🌟 Xush kelibsiz! Siz botimizga muvaffaqiyatli ulandingiz! \nEndi xabarlaringizni yuborishingiz mumkin"
                        ]);
                        Log::info('Start command response sent to user: ' . $chatId);
                    } else {
                        // Xabar turini aniqlash va guruhga yuborish
                        Log::info('Attempting to send message to group: ' . $this->targetGroupChatId);
                        try {
                            if ($message->getText()) {
                                $this->telegram->sendMessage([
                                    'chat_id' => $this->targetGroupChatId,
                                    'text' => $message->getText()
                                ]);
                                Log::info('Text message sent to group: ' . $message->getText());
                            } elseif ($message->getPhoto()) {
                                $photo = collect($message->getPhoto())->last();
                                $this->telegram->sendPhoto([
                                    'chat_id' => $this->targetGroupChatId,
                                    'photo' => $photo->getFileId(),
                                    'caption' => $message->getCaption() ?? ''
                                ]);
                                Log::info('Photo sent to group');
                            } elseif ($message->getVideo()) {
                                $this->telegram->sendVideo([
                                    'chat_id' => $this->targetGroupChatId,
                                    'video' => $message->getVideo()->getFileId(),
                                    'caption' => $message->getCaption() ?? ''
                                ]);
                                Log::info('Video sent to group');
                            } elseif ($message->getAudio()) {
                                $this->telegram->sendAudio([
                                    'chat_id' => $this->targetGroupChatId,
                                    'audio' => $message->getAudio()->getFileId(),
                                    'caption' => $message->getCaption() ?? ''
                                ]);
                                Log::info('Audio sent to group');
                            } elseif ($message->getDocument()) {
                                $this->telegram->sendDocument([
                                    'chat_id' => $this->targetGroupChatId,

                                    'document' => $message->getDocument()->getFileId(),
                                    'caption' => $message->getCaption() ?? ''
                                ]);
                                Log::info('Document sent to group');
                            } elseif ($message->getVoice()) {
                                $this->telegram->sendVoice([
                                    'chat_id' => $this->targetGroupChatId,
                                    'voice' => $message->getVoice()->getFileId(),
                                    'caption' => $message->getCaption() ?? ''
                                ]);
                                Log::info('Voice sent to group');
                            } elseif ($message->getVideoNote()) {
                                $this->telegram->sendVideoNote([
                                    'chat_id' => $this->targetGroupChatId,
                                    'video_note' => $message->getVideoNote()->getFileId()
                                ]);
                                Log::info('Video note sent to group');
                            } elseif ($message->getSticker()) {
                                $this->telegram->sendSticker([
                                    'chat_id' => $this->targetGroupChatId,
                                    'sticker' => $message->getSticker()->getFileId()
                                ]);
                                Log::info('Sticker sent to group');
                            }

                            // Foydalanuvchiga javob yuborish
                            $this->telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => "✅ Xabaringiz guruhga muvaffaqiyatli yuborildi!\n Keyingi xabarni yuboring!"
                            ]);
                            Log::info('Response sent to user: ' . $chatId);
                        } catch (TelegramSDKException $e) {
                            // Guruhga yuborishda xato yuzaga kelsa
                            Log::error('Failed to send message to group: ' . $e->getMessage());
                            $this->telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => "❌ Xabarni guruhga yuborishda xato yuz berdi. Iltimos, bot guruhda admin ekanligini tekshiring."
                            ]);
                        }
                    }
                } else {
                    Log::info('Unauthorized user tried to send message: ' . $chatId);
                }
            } else {
                Log::info('Non-message update received: ' . json_encode($update));
            }

            return response()->json(['status' => 'success']);
        } catch (TelegramSDKException $e) {
            // Xatolarni log qilish
            Log::error('Telegram Bot Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
