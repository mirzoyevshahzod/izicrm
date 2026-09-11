<?php

namespace App\Http\Controllers;

use App\Models\Mail;
use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramExpressBotController extends Controller
{
    protected $telegram;
    public $ofertaLink = 'https://express-bot.izisol.uz/oferta';

    public function __construct()
    {
        $this->telegram = new Api('8014306352:AAGDzBhv50So0s2yhoYwXOv-b4xrOBHuoSk');
    }

    /**
     * Telegram webhook handler
     *
     * @throws TelegramSDKException
     */
    public function handle(Request $request)
    {
        try {
            $update = $this->telegram->getWebhookUpdate();
            Log::info('Webhook Update Received:', ['update' => $update->toArray()]);

            $message = $update->getMessage();
            $callback = $update->getCallbackQuery();

            $chatId = $message ? $message->getChat()->getId() : ($callback ? $callback->getMessage()->getChat()->getId() : null);
            if (!$chatId) {
                Log::error('Chat ID Not Found:', ['update' => $update->toArray()]);
                return response('ok', 200);
            }

            $user = TelegramUser::firstOrCreate(
                ['chat_id' => $chatId],
                ['phone_number' => null, 'role' => null]
            );
            Log::info('User Registered or Updated:', [
                'chat_id' => $chatId,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'created_at' => $user->created_at
            ]);

            return $this->processStep($user, $message, $callback, $chatId);
        } catch (\Exception $e) {
            Log::error('Telegram Bot Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_id' => $chatId ?? 'unknown'
            ]);
            return response('ok', 200);
        }
    }

    /**
     * Process the appropriate step based on user role and input
     */
    protected function processStep($user, $message, $callback, $chatId)
    {
        Log::info('Processing Step:', [
            'user_role' => $user->role,
            'message_exists' => $message ? true : false,
            'callback_exists' => $callback ? true : false,
            'chat_id' => $chatId
        ]);

        if ($message && $message->getText() === '/start') {
            Log::info('Start Command Detected:', ['chat_id' => $chatId]);
            return $this->handleStart($user, $chatId);
        }

        if ($user->role === 'employer') {
            Log::info('Processing Employer Step:', ['chat_id' => $chatId]);
            $mail = Mail::firstOrCreate(['chat_id' => $chatId]);
            return $this->processEmployerSteps($user, $mail, $message, $callback, $chatId);
        }

        Log::info('Processing Client Step:', ['chat_id' => $chatId]);
        return $this->processClientSteps($user, $message, $callback, $chatId);
    }

    /**
     * Handle the /start command
     */
    /**
     * Handle the /start command
     */
    protected function handleStart($user, $chatId)
    {
        Log::info('Handling Start Command:', ['chat_id' => $chatId, 'role' => $user->role]);
        if ($user->role === 'employer') {
            $mail = Mail::firstOrCreate(['chat_id' => $chatId]);
            $mail->update(['step' => 'lang_select', 'lang' => null, 'images' => []]); // images maydonini bo‘shatamiz
            Log::info('Mail Initialized for Employer:', ['chat_id' => $chatId, 'step' => 'lang_select']);
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Tilni tanlang / Выберите язык",
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => "🇺🇿 O'zbekcha", 'callback_data' => 'lang_uz'],
                            ['text' => "🇷🇺 Русский", 'callback_data' => 'lang_ru'],
                        ]
                    ]
                ])
            ]);
        } elseif ($user->role === 'currier' || $user->role === null) {
            if (!$user->phone_number) {
                Log::info('Requesting Phone Number from Currier:', ['chat_id' => $chatId]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Iltimos, telefon raqamingizni yuboring:",
                    'reply_markup' => json_encode([
                        'keyboard' => [
                            [
                                [
                                    'text' => '📱 Raqamni yuborish',
                                    'request_contact' => true
                                ]
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ])
                ]);
            } else {
                // Telefon raqami mavjud bo'lsa, uni tekshirish
                $existingCurrier = TelegramUser::where('phone_number', $user->phone_number)
                    ->where('role', 'currier')
                    ->first();
                if ($existingCurrier) {
                    Log::info('Currier Already Registered:', ['chat_id' => $chatId, 'phone_number' => $user->phone_number]);
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Siz botga ulandingiz, zayavkani kuting.",
                        'reply_markup' => json_encode(['remove_keyboard' => true])
                    ]);
                } else {
                    // Telefon raqami mavjud emas, role ni currier qilib yangilash
                    $user->update(['role' => 'currier']);
                    Log::info('Currier Role Assigned:', ['chat_id' => $chatId, 'phone_number' => $user->phone_number]);
                    $this->telegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => "Siz botga ulandingiz, zayavkani kuting.",
                        'reply_markup' => json_encode(['remove_keyboard' => true])
                    ]);
                }
            }
        }
        return response('ok', 200);
    }

    /**
     * Process steps for employer users
     */
    protected function processEmployerSteps($user, $mail, $message, $callback, $chatId)
    {
        Log::info('Processing Employer Steps:', [
            'step' => $mail->step,
            'chat_id' => $chatId
        ]);

        switch ($mail->step) {
            case 'lang_select':
                if ($callback) {
                    Log::info('Language Selection Callback:', ['data' => $callback->getData(), 'chat_id' => $chatId]);
                    $data = $callback->getData();
                    $lang = $data === 'lang_uz' ? 'uz' : 'ru';
                    $mail->update(['lang' => $lang, 'step' => 'sender_name']);
                    Log::info('Language Updated:', ['lang' => $lang, 'new_step' => 'sender_name', 'chat_id' => $chatId]);

                    $this->telegram->answerCallbackQuery([
                        'callback_query_id' => $callback->getId(),
                        'text' => $lang === 'uz' ? "Til tanlandi" : "Язык выбран",
                        'show_alert' => false
                    ]);

                    $responseText = $lang === 'uz' ? "Yuboruvchining to‘liq ismini kiriting (F.I.O):" : "Введите полное имя отправителя:";
                    $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                }
                break;

            case 'sender_name':
                if ($message) {
                    Log::info('Sender Name Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['sender_name' => $text, 'step' => 'sender_address']);
                        Log::info('Sender Name Updated:', ['name' => $text, 'new_step' => 'sender_address', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Yuboruvchi manzilini kiriting:" : "Введите адрес отправителя:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri ism kiriting (kamida 3 belgi)." : "Пожалуйста, введите корректное имя (минимум 3 символа).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Sender Name:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'sender_address':
                if ($message) {
                    Log::info('Sender Address Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['sender_address' => $text, 'step' => 'sender_phone']);
                        Log::info('Sender Address Updated:', ['address' => $text, 'new_step' => 'sender_phone', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Telefon raqamini kiriting:" : "Введите номер телефона:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri manzil kiriting (kamida 5 belgi)." : "Пожалуйста, введите корректный адрес (минимум 5 символов).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Sender Address:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'sender_phone':
                if ($message) {
                    Log::info('Sender Phone Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (preg_match('/^\+\d{1,3}\d{6,12}$/', $text)) {
                        $mail->update(['sender_phone' => $text, 'step' => 'recipient_name']);
                        Log::info('Sender Phone Updated:', ['phone' => $text, 'new_step' => 'recipient_name', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Qabul qiluvchining to'liq ismini kiriting (F.I.O):" : "Введите полное имя получателя:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri telefon raqamini kiriting (masalan, +12025550123 yoki +998901234567)." : "Пожалуйста, введите корректный номер телефона (например, +12025550123 или +998901234567).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Sender Phone:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'recipient_name':
                if ($message) {
                    Log::info('Recipient Name Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['recipient_name' => $text, 'step' => 'recipient_address']);
                        Log::info('Recipient Name Updated:', ['name' => $text, 'new_step' => 'recipient_address', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Qabul qiluvchining manzilini kiriting:" : "Введите адрес получателя:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri ism kiriting (kamida 3 belgi)." : "Пожалуйста, введите корректное имя (минимум 3 символа).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Recipient Name:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'recipient_address':
                if ($message) {
                    Log::info('Recipient Address Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['recipient_address' => $text, 'step' => 'recipient_country']);
                        Log::info('Recipient Address Updated:', ['address' => $text, 'new_step' => 'recipient_country', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Qabul qiluvchining davlatini kiriting:" : "Введите страну получателя:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri manzil kiriting (kamida 5 belgi)." : "Пожалуйста, введите корректный адрес (минимум 5 символов).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Recipient Address:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'recipient_country':
                if ($message) {
                    Log::info('Recipient Country Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['recipient_country' => $text, 'step' => 'recipient_index']);
                        Log::info('Recipient Country Updated:', ['country' => $text, 'new_step' => 'recipient_index', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Qabul qiluvchining indeksini kiriting:" : "Введите индекс получателя:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri davlat nomini kiriting (kamida 3 belgi)." : "Пожалуйста, введите корректное название страны (минимум 3 символа).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Recipient Country:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'recipient_index':
                if ($message) {
                    Log::info('Recipient Index Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && strlen($text) > 2) {
                        $mail->update(['recipient_postal_code' => $text, 'step' => 'tarif']);
                        Log::info('Recipient Index Updated:', ['index' => $text, 'new_step' => 'tarif', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Tarifni tanlang:" : "Выберите тариф:";
                        $keyboard = $mail->lang === 'uz' ? [
                            [['text' => "✅ Ekonom (to 14 kun)", 'callback_data' => 'tarif_ekonom']],
                            [['text' => "🚚 Standart (5–10 kun)", 'callback_data' => 'tarif_standart']],
                            [['text' => "🚀 Ekspress (2–5 kun)", 'callback_data' => 'tarif_ekspress']],
                            [['text' => "🕐 Super-ekspress (24–72 soat)", 'callback_data' => 'tarif_super']],
                        ] : [
                            [['text' => "✅ Эконом (до 14 дней)", 'callback_data' => 'tarif_ekonom']],
                            [['text' => "🚚 Стандарт (5–10 дней)", 'callback_data' => 'tarif_standart']],
                            [['text' => "🚀 Экспресс (2–5 дней)", 'callback_data' => 'tarif_ekspress']],
                            [['text' => "🕐 Супер-экспресс (24–72 часа)", 'callback_data' => 'tarif_super']],
                        ];
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $responseText,
                            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                        ]);
                    } else {
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri indeks kiriting (kamida 3 belgi)." : "Пожалуйста, введите корректный индекс (минимум 3 символа).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        Log::warning('Invalid Recipient Index:', ['text' => $text, 'chat_id' => $chatId]);
                    }
                }
                break;

            case 'tarif':
                if ($callback) {
                    Log::info('Tariff Selection Callback:', [
                        'data' => $callback->getData(),
                        'chat_id' => $chatId
                    ]);
                    $data = $callback->getData();
                    $availableTariffs = [
                        'tarif_ekonom' => $mail->lang === 'uz' ? '✅ Ekonom (to 14 kun)' : '✅ Эконом (до 14 дней)',
                        'tarif_standart' => $mail->lang === 'uz' ? '🚚 Standart (5–10 kun)' : '🚚 Стандарт (5–10 дней)',
                        'tarif_ekspress' => $mail->lang === 'uz' ? '🚀 Ekspress (2–5 kun)' : '🚀 Экспресс (2–5 дней)',
                        'tarif_super' => $mail->lang === 'uz' ? '🕐 Super-ekspress (24–72 soat)' : '🕐 Супер-экспресс (24–72 часа)'
                    ];
                    $selectedTarif = $availableTariffs[$data] ?? null;
                    if ($selectedTarif) {
                        $mail->update(['tarif' => $selectedTarif, 'step' => 'delivery_time']);
                        Log::info('Tariff Updated:', ['tarif' => $selectedTarif, 'new_step' => 'delivery_time', 'chat_id' => $chatId]);
                        $this->telegram->answerCallbackQuery([
                            'callback_query_id' => $callback->getId(),
                            'text' => $mail->lang === 'uz' ? 'Tarif tanlandi' : 'Тариф выбран',
                            'show_alert' => false
                        ]);
                        $deliveryPrompt = $mail->lang === 'uz' ? 'Yetkazib berish muddatini kiriting:' : 'Введите срок доставки:';
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $deliveryPrompt]);
                    } else {
                        Log::warning('Invalid Tariff Selected:', ['data' => $data, 'chat_id' => $chatId]);
                        $this->telegram->answerCallbackQuery([
                            'callback_query_id' => $callback->getId(),
                            'text' => $mail->lang === 'uz' ? 'Noto‘g‘ri tarif tanlandi' : 'Выбран неверный тариф',
                            'show_alert' => true
                        ]);
                    }
                }
                break;

            case 'delivery_time':
                if ($message) {
                    Log::info('Delivery Time Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && is_numeric($text) && $text > 0) {
                        $mail->update(['delivery_time' => $text, 'step' => 'delivery_price']);
                        Log::info('Delivery Time Updated:', ['time' => $text, 'new_step' => 'delivery_price', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Yetkazib berish narxini kiriting:" : "Введите цену доставки:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        Log::warning('Invalid Delivery Time:', ['text' => $text, 'chat_id' => $chatId]);
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri muddat kiriting (raqam va 0 dan katta bo‘lishi kerak)." : "Пожалуйста, введите корректный срок (должен быть числом и больше 0).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                    }
                }
                break;

            case 'delivery_price':
                if ($message) {
                    Log::info('Delivery Price Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (trim($text) && is_numeric($text) && $text >= 0) {
                        $mail->update(['delivery_price' => $text, 'step' => 'currier_phone']);
                        Log::info('Delivery Price Updated:', ['price' => $text, 'new_step' => 'currier_phone', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Kuryer telefon raqamini kiriting:" : "Введите номер телефона курьера:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        Log::warning('Invalid Delivery Price:', ['text' => $text, 'chat_id' => $chatId]);
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri narx kiriting (raqam va 0 dan katta yoki teng bo‘lishi kerak)." : "Пожалуйста, введите корректную цену (должно быть числом и больше или равно 0).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                    }
                }
                break;

            case 'currier_phone':
                if ($message) {
                    Log::info('Currier Phone Input:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                    $text = $message->getText();
                    if (preg_match('/^\+\d{1,3}\d{6,12}$/', $text)) {
                        $mail->update(['currier_phone' => $text, 'step' => 'upload_images']);
                        Log::info('Currier Phone Updated:', ['currier_phone' => $text, 'new_step' => 'upload_images', 'chat_id' => $chatId]);
                        $responseText = $mail->lang === 'uz' ? "Rasmlarni yuboring:" : "Отправьте изображения:";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $responseText]);
                    } else {
                        Log::warning('Invalid Currier Phone:', ['text' => $text, 'chat_id' => $chatId]);
                        $errorText = $mail->lang === 'uz' ? "Iltimos, to‘g‘ri telefon raqamini kiriting (masalan, +998901234567)." : "Пожалуйста, введите корректный номер телефона (например, +998901234567).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                    }
                }
                break;

            case 'upload_images':
                if (!$message) {
                    Log::warning('Message Object Missing:', ['chat_id' => $chatId]);
                    $errorText = $mail->lang === 'uz' ? "Xatolik: Xabar topilmadi." : "Ошибка: Сообщение не найдено.";
                    $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                    break;
                }

                Log::info('Starting Image Upload Process:', ['chat_id' => $chatId, 'step' => 'upload_images']);

                $fileIds = []; // Hozirgi zayavka uchun yangi massiv yaratamiz
                $fileId = null;

                // 1. Rasmni olishga harakat qilamiz
                Log::info('Attempting to Get Photos:', ['chat_id' => $chatId]);
                $photos = $message->getPhoto();
                Log::info('Photos Retrieved:', ['photos' => json_encode($photos), 'type' => gettype($photos), 'is_collection' => $photos instanceof \Illuminate\Support\Collection, 'chat_id' => $chatId]);

                // 2. Photos mavjudligini va to‘g‘ri formatda ekanligini tekshiramiz
                if (($photos && is_array($photos)) || $photos instanceof \Illuminate\Support\Collection) {
                    Log::info('Photos Found, Processing:', ['photos_count' => $photos instanceof \Illuminate\Support\Collection ? $photos->count() : count($photos), 'chat_id' => $chatId]);

                    // 3. Eng katta rasmni olish
                    $largestPhoto = $photos instanceof \Illuminate\Support\Collection ? $photos->last() : end($photos);
                    Log::info('Largest Photo Selected:', [
                        'largest_photo' => json_encode($largestPhoto),
                        'class' => get_class($largestPhoto) ?? 'null',
                        'chat_id' => $chatId
                    ]);

                    // 4. Rasm obyektini tekshirish
                    if ($largestPhoto instanceof \Telegram\Bot\Objects\PhotoSize) {
                        Log::info('Largest Photo is PhotoSize Object:', ['chat_id' => $chatId]);

                        // 5. file_id ni olishga harakat qilamiz
                        try {
                            // Agar getFileId() metodi mavjud bo‘lsa, undan foydalanamiz
                            if (method_exists($largestPhoto, 'getFileId')) {
                                $fileId = $largestPhoto->getFileId();
                                Log::info('File ID Retrieved via getFileId():', ['file_id' => $fileId, 'chat_id' => $chatId]);
                            } else {
                                // Agar getFileId() mavjud bo‘lmasa, to‘g‘ridan-to‘g‘ri xususiyatdan olishga harakat qilamiz
                                Log::info('getFileId() Method Not Found, Trying Direct Access:', ['chat_id' => $chatId]);
                                $fileId = $largestPhoto->file_id ?? null;
                                Log::info('File ID Retrieved via Direct Access:', ['file_id' => $fileId, 'chat_id' => $chatId]);
                            }

                            if (empty($fileId)) {
                                Log::warning('File ID is Empty:', ['largest_photo' => json_encode($largestPhoto), 'chat_id' => $chatId]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Error Retrieving File ID:', ['error' => $e->getMessage(), 'largest_photo' => json_encode($largestPhoto), 'chat_id' => $chatId]);
                        }
                    } else {
                        Log::warning('Largest Photo is Not a PhotoSize Object:', [
                            'largest_photo' => json_encode($largestPhoto),
                            'class' => get_class($largestPhoto) ?? 'null',
                            'chat_id' => $chatId
                        ]);
                    }
                } else {
                    Log::info('No Photos Found, Checking for Document:', ['chat_id' => $chatId]);

                    // 6. Dokument sifatida rasm yuborilgan bo‘lsa
                    $document = $message->getDocument();
                    if ($document && str_starts_with($document->getMimeType() ?? '', 'image/')) {
                        Log::info('Document Found with Image MIME Type:', ['mime_type' => $document->getMimeType(), 'chat_id' => $chatId]);
                        try {
                            $fileId = $document->getFileId();
                            Log::info('Image Received (Document):', ['file_id' => $fileId, 'chat_id' => $chatId]);
                        } catch (\Exception $e) {
                            Log::error('Error Retrieving Document File ID:', ['error' => $e->getMessage(), 'chat_id' => $chatId]);
                        }
                    } else {
                        Log::warning('Invalid Image Input:', ['chat_id' => $chatId, 'message' => json_encode($message)]);
                        $errorText = $mail->lang === 'uz' ? "Iltimos, faqat rasm yuboring (PNG, JPEG yoki boshqa formatda)." : "Пожалуйста, отправьте только изображение (в формате PNG, JPEG или другом).";
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $errorText]);
                        break;
                    }
                }

                // 7. File ID olingan bo‘lsa, keyingi qadamlarni bajarish
                if ($fileId) {
                    Log::info('Proceeding with File ID:', ['file_id' => $fileId, 'chat_id' => $chatId]);
                    $fileIds[] = $fileId; // Faqat hozirgi rasmni qo‘shamiz
                    $mail->update(['images' => $fileIds, 'step' => 'completed', 'status' => 'pending']);
                    Log::info('Images Updated:', ['images' => $fileIds, 'new_step' => 'completed', 'chat_id' => $chatId]);

                    // Employer uchun xulosa yuborish
                    $summaryTextEmployer = $mail->lang === 'uz'
                        ? "Ma'lumotlaringiz muvaffaqiyatli saqlandi!\n\nYuboruvchi: {$mail->sender_name}\nYuboruvchi manzili: {$mail->sender_address}\nTelefon raqami: {$mail->sender_phone}\nQabul qiluvchi: {$mail->recipient_name}\nQabul qiluvchi manzili: {$mail->recipient_address}\nDavlat: {$mail->recipient_country}\nIndeks: {$mail->recipient_postal_code}\nTanlangan tarif: {$mail->tarif}\nYetkazib berish muddati: {$mail->delivery_time} kun\nYetkazib berish narxi: {$mail->delivery_price} so'm\nStatus: {$mail->status}\n\nYana pochta jo‘natmoqchi bo‘lsangiz, /start ni bosing."
                        : "Ваши данные успешно сохранены!\n\nОтправитель: {$mail->sender_name}\nАдрес отправителя: {$mail->sender_address}\nНомер телефона: {$mail->sender_phone}\nПолучатель: {$mail->recipient_name}\nАдрес получателя: {$mail->recipient_address}\nСтрана: {$mail->recipient_country}\nИндекс: {$mail->recipient_postal_code}\nВыбранный тариф: {$mail->tarif}\nСрок доставки: {$mail->delivery_time} дней\nЦена доставки: {$mail->delivery_price} руб.\nСтатус: {$mail->status}\n\nЕсли хотите отправить ещё одну посылку, нажмите /start.";

                    $summaryTextClient = $mail->lang === 'uz'
                        ? "Ma'lumotlaringiz muvaffaqiyatli saqlandi!\n\nYuboruvchi: {$mail->sender_name}\nYuboruvchi manzili: {$mail->sender_address}\nTelefon raqami: {$mail->sender_phone}\nQabul qiluvchi: {$mail->recipient_name}\nQabul qiluvchi manzili: {$mail->recipient_address}\nDavlat: {$mail->recipient_country}\nIndeks: {$mail->recipient_postal_code}\nTanlangan tarif: {$mail->tarif}\nYetkazib berish muddati: {$mail->delivery_time} kun\nYetkazib berish narxi: {$mail->delivery_price} so'm"
                        : "Ваши данные успешно сохранены!\n\nОтправитель: {$mail->sender_name}\nАдрес отправителя: {$mail->sender_address}\nНомер телефона: {$mail->sender_phone}\nПолучатель: {$mail->recipient_name}\nАдрес получателя: {$mail->recipient_address}\nСтрана: {$mail->recipient_country}\nИндекс: {$mail->recipient_postal_code}\nВыбранный тариф: {$mail->tarif}\nСрок доставки: {$mail->delivery_time} дней\nЦена доставки: {$mail->delivery_price} руб.";

                    try {
                        $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => $summaryTextEmployer]);
                        Log::info('Summary Sent to Employer:', ['chat_id' => $chatId, 'summary' => $summaryTextEmployer]);
                    } catch (\Exception $e) {
                        Log::error('Error Sending Summary to Employer:', ['error' => $e->getMessage(), 'chat_id' => $chatId]);
                    }

                    // Clientga ma’lumotlar va rasmlarni yuborish
                    $recipient = TelegramUser::where('phone_number', $mail->sender_phone)->whereNull('role')->first();
                    if ($recipient && $recipient->chat_id != $chatId) {
                        Log::info('Recipient Found, Sending Data:', ['recipient_chat_id' => $recipient->chat_id, 'chat_id' => $chatId]);

                        // Rasmlarni yuborish
                        if (!empty($mail->images)) {
                            foreach ($mail->images as $fileId) {
                                try {
                                    $this->telegram->sendPhoto([
                                        'chat_id' => $recipient->chat_id,
                                        'photo' => $fileId,
                                    ]);
                                    Log::info('Image Sent to Client:', ['file_id' => $fileId, 'recipient_chat_id' => $recipient->chat_id]);
                                } catch (\Exception $e) {
                                    Log::error('Error Sending Image to Client:', ['file_id' => $fileId, 'recipient_chat_id' => $recipient->chat_id, 'error' => $e->getMessage()]);
                                }
                            }
                        }

                        // Ma’lumotlar va tugmalar
                        try {
                            $this->telegram->sendMessage([
                                'chat_id' => $recipient->chat_id,
                                'text' => $summaryTextClient,
                                'reply_markup' => json_encode([
                                    'inline_keyboard' => [
                                        [
                                            ['text' => $mail->lang === 'uz' ? '✅ Accept' : '✅ Принять', 'callback_data' => 'action_accept_' . $mail->id],
                                            ['text' => $mail->lang === 'uz' ? '❌ Reject' : '❌ Отклонить', 'callback_data' => 'action_reject_' . $mail->id]
                                        ],
                                    ]
                                ])
                            ]);
                            Log::info('Summary Sent to Client:', [
                                'recipient_chat_id' => $recipient->chat_id,
                                'summary' => $summaryTextClient,
                                'mail_id' => $mail->id
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Error Sending Summary to Client:', ['recipient_chat_id' => $recipient->chat_id, 'error' => $e->getMessage()]);
                        }
                    } else {
                        Log::warning('Recipient Not Found or Matches Sender:', [
                            'phone_number' => $mail->sender_phone,
                            'chat_id' => $chatId
                        ]);
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $mail->lang === 'uz'
                                ? "Ushbu telefon raqamiga bog‘langan foydalanuvchi botda ro‘yxatdan o‘tmagan yoki sizning chattingiz bilan bir xil. Iltimos, ularga botga /start bilan qo‘shilishni so‘rang."
                                : "Пользователь с этим номером телефона не зарегистрирован в боте или совпадает с вашим чатом. Попросите их подключиться, нажав /start.",
                        ]);
                    }
                } else {
                    Log::warning('No File ID Retrieved, Process Stopped:', ['chat_id' => $chatId]);
                }

                Log::info('Image Upload Process Completed:', ['chat_id' => $chatId, 'success' => !empty($fileId)]);
                break;
        }

        return response('ok', 200);
    }

    /**
     * Process steps for client users
     */
    protected function processClientSteps($user, $message, $callback, $chatId)
    {
        Log::info('Processing Client Steps:', [
            'chat_id' => $chatId,
            'message_exists' => $message ? true : false,
            'callback_exists' => $callback ? true : false
        ]);

        // Telefon raqamini so‘rash faqat phone_number null bo‘lsa va message kontakt yoki raqam kiritish uchun bo‘lsa
        if ($message && !$user->phone_number) {
            Log::info('Processing Message Input for Phone Number:', ['text' => $message->getText(), 'chat_id' => $chatId]);
            $phoneNumber = null;

            if ($message->getContact()) {
                Log::info('Contact Received:', ['chat_id' => $chatId]);
                $phoneNumber = $message->getContact()->getPhoneNumber();
                if (!str_starts_with($phoneNumber, '+')) {
                    $phoneNumber = '+' . $phoneNumber;
                }
            } else {
                Log::info('Text Input Received:', ['text' => $message->getText(), 'chat_id' => $chatId]);
                $text = $message->getText();
                $cleanedPhone = preg_replace('/\s+/', '', $text);
                $phoneNumber = $cleanedPhone;
            }

            if ($phoneNumber && preg_match('/^\+\d{1,3}\d{6,12}$/', $phoneNumber)) {
                $user->update(['phone_number' => $phoneNumber]);
                Log::info('Client Phone Number Saved:', ['chat_id' => $chatId, 'phone_number' => $phoneNumber]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Siz botga ulandingiz, zayavkani kuting.",
                    'reply_markup' => json_encode(['remove_keyboard' => true])
                ]);
            } else {
                Log::warning('Invalid Phone Number:', ['phone_number' => $phoneNumber, 'chat_id' => $chatId]);
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Iltimos, to‘g‘ri telefon raqamini kiriting (masalan, +998901234567).",
                ]);
            }
        } elseif ($message) {
            Log::info('Message Received but Not Processing as Phone Number:', ['text' => $message->getText(), 'chat_id' => $chatId]);
        }

        if ($callback) {
            Log::info('Processing Callback Query:', [
                'data' => $callback->getData(),
                'chat_id' => $chatId
            ]);
            $data = $callback->getData();
            $parts = explode('_', $data);
            if (count($parts) < 2) {
                Log::error('Invalid Callback Data Format:', ['data' => $data, 'chat_id' => $chatId]);
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "Xatolik: Noto‘g‘ri ma'lumot formati."]);
                return response('ok', 200);
            }

            $action = implode('_', array_slice($parts, 0, 2));
            $mailId = (int)end($parts);
            Log::info('Parsed Callback:', ['action' => $action, 'mail_id' => $mailId, 'chat_id' => $chatId]);

            $mail = Mail::find($mailId);
            if ($mail) {
                Log::info('Mail Found:', ['mail_id' => $mailId, 'chat_id' => $chatId, 'mail_chat_id' => $mail->chat_id]);
                switch ($action) {
                    case 'action_accept':
                        Log::info('Action Accept Triggered:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                        $mail->update(['oferta_checked' => true]); // Oferta ko‘rib chiqildi deb belgilash
                        Log::info('Oferta Checked Updated:', ['mail_id' => $mailId, 'oferta_checked' => true]);
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $mail->lang === 'uz'
                                ? "Ofertani tekshiring va tasdiqlang: " . $this->ofertaLink
                                : "Проверьте и подтвердите оффер: " . $this->ofertaLink,
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [
                                        ['text' => $mail->lang === 'uz' ? '✅ Ofertani tasdiqlash' : '✅ Подтвердить оффер', 'callback_data' => 'confirm_oferta_' . $mailId]
                                    ]
                                ]
                            ])
                        ]);
                        break;

                    case 'action_reject':
                        Log::info('Action Reject Triggered:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                        $mail->update(['status' => 'rejected']);
                        Log::info('Mail Status Updated:', ['mail_id' => $mailId, 'new_status' => 'rejected']);
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $mail->lang === 'uz' ? "Siz zayavkani bekor qildingiz." : "Вы отменили заявку."
                        ]);
                        $this->telegram->sendMessage([
                            'chat_id' => $mail->chat_id,
                            'text' => $mail->lang === 'uz' ? "Mijoz zayavkani rad etdi." : "Клиент отклонил заявку."
                        ]);
                        break;

                    case 'action_oferta':
                        Log::info('Action Oferta Triggered:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                        $mail->update(['oferta_checked' => true]);
                        Log::info('Oferta Checked Updated:', ['mail_id' => $mailId, 'oferta_checked' => true]);
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $mail->lang === 'uz' ? "Oferta ko‘rib chiqildi: https://1972-93-188-81-10.ngrok-free.app/oferta" : "Оффер просмотрен: https://1972-93-188-81-10.ngrok-free.app/oferta",
                            'reply_markup' => json_encode([
                                'inline_keyboard' => [
                                    [
                                        ['text' => $mail->lang === 'uz' ? '✅ Tasdiqlash' : '✅ Подтвердить', 'callback_data' => 'confirm_oferta_' . $mailId]
                                    ]
                                ]
                            ])
                        ]);
                        break;

                    case 'confirm_oferta':
                        Log::info('Confirm Oferta Triggered:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                        if ($mail->oferta_checked) {
                            $mail->update(['status' => 'accepted']);
                            Log::info('Mail Status Updated:', ['mail_id' => $mailId, 'new_status' => 'accepted']);

                            // Client’ga xabar yuborish
                            $this->telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => $mail->lang === 'uz' ? "Siz zayavkani qabul qildingiz va shartlarga rozi bo‘ldingiz." : "Вы приняли заявку и согласились с условиями."
                            ]);

                            // Employer’ga xabar yuborish
                            $this->telegram->sendMessage([
                                'chat_id' => $mail->chat_id,
                                'text' => $mail->lang === 'uz' ? "Mijoz zayavka bilan tanishdi va ofertani qabul qildi." : "Клиент принял заявку и оффер подтвержден."
                            ]);

                            // Kuryerni topish va zayavkani yuborish
                            $currier = TelegramUser::where('phone_number', $mail->currier_phone)
                                ->where('role', 'currier')
                                ->first();

                            if ($currier) {
                                Log::info('Currier Found, Sending Order Details:', [
                                    'currier_chat_id' => $currier->chat_id,
                                    'mail_id' => $mail->id
                                ]);

                                // Kuryerga yuboriladigan xabar
                                $summaryTextCurrier = $mail->lang === 'uz'
                                    ? "Yangi zayavka:\n\nYuboruvchi: {$mail->sender_name}\nYuboruvchi manzili: {$mail->sender_address}\nTelefon raqami: {$mail->sender_phone}\nQabul qiluvchi: {$mail->recipient_name}\nQabul qiluvchi manzili: {$mail->recipient_address}\nDavlat: {$mail->recipient_country}\nIndeks: {$mail->recipient_postal_code}\nTanlangan tarif: {$mail->tarif}\nYetkazib berish muddati: {$mail->delivery_time} kun\nYetkazib berish narxi: {$mail->delivery_price} so'm"
                                    : "Новая заявка:\n\nОтправитель: {$mail->sender_name}\nАдрес отправителя: {$mail->sender_address}\nНомер телефона: {$mail->sender_phone}\nПолучатель: {$mail->recipient_name}\nАдрес получателя: {$mail->recipient_address}\nСтрана: {$mail->recipient_country}\nИндекс: {$mail->recipient_postal_code}\nВыбранный тариф: {$mail->tarif}\nСрок доставки: {$mail->delivery_time} дней\nЦена доставки: {$mail->delivery_price} руб.";

                                // Rasmlarni kuryerga yuborish
                                if (!empty($mail->images)) {
                                    foreach ($mail->images as $fileId) {
                                        try {
                                            $this->telegram->sendPhoto([
                                                'chat_id' => $currier->chat_id,
                                                'photo' => $fileId,
                                            ]);
                                            Log::info('Image Sent to Currier:', [
                                                'file_id' => $fileId,
                                                'currier_chat_id' => $currier->chat_id
                                            ]);
                                        } catch (\Exception $e) {
                                            Log::error('Error Sending Image to Currier:', [
                                                'file_id' => $fileId,
                                                'currier_chat_id' => $currier->chat_id,
                                                'error' => $e->getMessage()
                                            ]);
                                        }
                                    }
                                }

                                // Kuryerga zayavka ma’lumotlari va faqat "Zayavkani qabul qilish" tugmasi yuborish
                                try {
                                    $this->telegram->sendMessage([
                                        'chat_id' => $currier->chat_id,
                                        'text' => $summaryTextCurrier,
                                        'reply_markup' => json_encode([
                                            'inline_keyboard' => [
                                                [
                                                    ['text' => $mail->lang === 'uz' ? '✅ Zayavkani qabul qilish' : '✅ Принять заявку', 'callback_data' => 'currier_accept_' . $mail->id]
                                                ],
                                            ]
                                        ])
                                    ]);
                                    Log::info('Order Details Sent to Currier:', [
                                        'currier_chat_id' => $currier->chat_id,
                                        'summary' => $summaryTextCurrier,
                                        'mail_id' => $mail->id
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('Error Sending Order Details to Currier:', [
                                        'currier_chat_id' => $currier->chat_id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            } else {
                                Log::warning('Currier Not Found:', [
                                    'currier_phone' => $mail->currier_phone,
                                    'chat_id' => $chatId
                                ]);
                                $this->telegram->sendMessage([
                                    'chat_id' => $mail->chat_id,
                                    'text' => $mail->lang === 'uz'
                                        ? "Kuryer topilmadi. Iltimos, kuryer telefon raqamini tekshiring."
                                        : "Курьер не найден. Пожалуйста, проверьте номер телефона курьера."
                                ]);
                            }
                        } else {
                            Log::warning('Oferta Not Checked:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                            $this->telegram->sendMessage([
                                'chat_id' => $chatId,
                                'text' => $mail->lang === 'uz' ? "Iltimos, ofertani avval tekshiring!" : "Пожалуйста, сначала проверьте оффер!"
                            ]);
                        }
                        break;

                    case 'currier_accept':
                        Log::info('Currier Accept Triggered:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                        $mail->update(['status' => 'currier_accepted']);
                        Log::info('Mail Status Updated:', ['mail_id' => $mailId, 'new_status' => 'currier_accepted']);

                        // Kuryerga tasdiqlash xabari
                        $this->telegram->sendMessage([
                            'chat_id' => $chatId,
                            'text' => $mail->lang === 'uz' ? "Siz zayavkani qabul qildingiz." : "Вы приняли заявку."
                        ]);

                        // Client’ga xabar yuborish (recipient topilsa)
                        $recipient = TelegramUser::where('phone_number', $mail->sender_phone)->whereNull('role')->first();
                        if ($recipient && $recipient->chat_id != $mail->chat_id) {
                            $this->telegram->sendMessage([
                                'chat_id' => $recipient->chat_id,
                                'text' => $mail->lang === 'uz'
                                    ? "{$mail->currier_phone} raqamli kuryer zayavkani oldi"
                                    : "Курьер с номером {$mail->currier_phone} принял заявку",
                                'parse_mode' => 'Markdown' // Markdown ishlatilayotganini belgilash
                            ])->parameters([
                                'reply_markup' => json_encode(['remove_keyboard' => true])
                            ]);
                        }

                        // Employer’ga xabar yuborish
                        $this->telegram->sendMessage([
                            'chat_id' => $mail->chat_id,
                            'text' => $mail->lang === 'uz' ? "{$mail->currier_phone} Kuryer zayavkani oldi." : "{$mail->currier_phone} Курьер принял заявку."
                        ]);
                        break;

                    default:
                        Log::warning('Unknown Action:', ['action' => $action, 'mail_id' => $mailId, 'chat_id' => $chatId]);
                        break;


                }
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callback->getId(),
                    'show_alert' => false
                ]);
                Log::info('Callback Answered:', ['callback_id' => $callback->getId(), 'chat_id' => $chatId]);
            } else {
                Log::error('Mail Not Found:', ['mail_id' => $mailId, 'chat_id' => $chatId]);
                $this->telegram->sendMessage(['chat_id' => $chatId, 'text' => "Xatolik: Zayavka topilmadi."]);
            }
        }

        return response('ok', 200);
    }
}
