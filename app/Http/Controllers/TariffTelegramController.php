<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\File;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\TelegramState;
use App\Models\Setting;

class TariffTelegramController extends Controller
{
    private $token;
    private $apiUrl;

    private $adminId;

    private $superAdminId;

    public function __construct()
    {
        $this->token = config('services.telegram.telegram_tariff_bot_token');
        $this->apiUrl = 'https://api.telegram.org/bot' . $this->token . '/';
        $this->superAdminId = config('services.telegram.telegram_super_admin_id');
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

        return response()->json([
            'status' => 'ok'
        ]);
    }

    private function handleMessage(array $message)
    {
        $chatId = $message['chat']['id'];
        $lastName = $message['chat']['last_name'] ?? '';
        $firstName = $message['chat']['first_name'] ?? '';


        // /start
        if (
            isset($message['text']) &&
            $message['text'] === '/start'
        ) {

            if ($this->hasAdminAccess($chatId)) {
                $admin = Admin::query()
                    ->where('telegram_id', $chatId)
                    ->first();

                $admin?->update([
                    'name' => $firstName . ' ' . $lastName
                ]);

                $this->sendMessage(
                    $chatId,
                    "Admin botiga xush kelibsiz."
                );

            } else {

                $keyboard = [
                    'keyboard' => [
                        [
                            [
                                'text' => '📱 Telefon raqam yuborish',
                                'request_contact' => true
                            ]
                        ]
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ];

                $this->sendMessage(
                    $chatId,
                    "Iltimos telefon raqamingizni yuboring:",
                    $keyboard
                );
            }
        }

        $telegramState = TelegramState::query()
            ->where('chat_id', $chatId)
            ->first();

        if (
            isset($message['users_shared']) &&
            $this->isSuperAdmin($chatId)
        ) {

            $users = $message['users_shared']['users'];

            foreach ($users as $user) {

                $telegramId = $user['user_id'];

                $name = $user['first_name'] ?? 'Admin';

                Admin::query()->firstOrCreate(
                    [
                        'telegram_id' => $telegramId
                    ],
                    [
                        'name' => $name
                    ]
                );

                $removeKeyboard = [
                    'remove_keyboard' => true
                ];

                $this->sendMessage(
                    $chatId,
                    "✅ {$telegramId} admin qilindi.",
                    $removeKeyboard
                );
            }

            return;
        }

        if (
            isset($message['text']) &&
            $message['text'] === '/admins' &&
            $this->hasAdminAccess($chatId)
        ) {

            $admins = Admin::query()->get();

            $text =
                "👮 ADMINLAR RO‘YXATI\n\n"

                . "👑 Super Admin:\n"
                . "{$this->superAdminId}\n\n"

                . "👥 Oddiy adminlar:\n";

            if ($admins->isEmpty()) {

                $text .= "Adminlar mavjud emas.";
            }

            foreach ($admins as $index => $admin) {

                $number = $index + 1;

                $text .=
                    "{$number}. {$admin->name} - {$admin->telegram_id}\n";
            }

            $this->sendMessage(
                $chatId,
                $text
            );

            return;
        }

        if (
            $telegramState &&
            $telegramState->state &&
            $telegramState->state !== 'add_admin' &&
            isset($message['text']) &&
            $this->hasAdminAccess($chatId)
        ) {

            $setting = Setting::query()->first();

            if (!$setting) {

                $setting = Setting::query()->create([
                    'standard_price' => 100000,
                    'premium_price' => 200000,
                ]);
            }

            $setting->update([
                $telegramState->state => $message['text']
            ]);

            $telegramState->update([
                'state' => null
            ]);

            $this->sendMessage(
                $chatId,
                "✅ Ma'lumot yangilandi."
            );

            return;
        }

        if (
            isset($message['text']) &&
            str_starts_with($message['text'], '/subscription') &&
            $this->hasAdminAccess($chatId)
        ) {

            $textParts = explode(' ', trim($message['text']));

            $phone = $textParts[1] ?? null;

            $query = Subscription::query()
                ->with('files')
                ->latest();

            // telefon yozilgan bo'lsa filter qiladi
            if ($phone) {

                $query->where('phone', 'like', '%' . $phone . '%');
            }

            $subscriptions = $query->get();

            if ($subscriptions->isEmpty()) {

                $this->sendMessage(
                    $chatId,
                    "❌ Obuna topilmadi."
                );

                return;
            }

            foreach ($subscriptions as $subscription) {

                $status = match ($subscription->status) {
                    'approved' => '✅ Tasdiqlangan',
                    'rejected' => '❌ Rad etilgan',
                    default => '⏳ Kutilmoqda',
                };

                // file bo'lsa
                if ($subscription->files->count()) {

                    $media = [];

                    foreach ($subscription->files as $index => $file) {

                        $filePath = storage_path(
                            'app/public/' . $file->file_path
                        );

                        if (!file_exists($filePath)) {
                            continue;
                        }

                        $attachName = 'photo' . $index;

                        $mediaItem = [
                            'type' => 'photo',
                            'media' => 'attach://' . $attachName,
                        ];

                        if ($index === 0) {

                            $mediaItem['caption'] =
                                "📋 OBUNA MA'LUMOTI\n\n"
                                . "🆔 ID: {$subscription->id}\n"
                                . "👤 Ism: {$subscription->name}\n"
                                . "📞 Telefon: {$subscription->phone}\n"
                                . "💳 Tarif: {$subscription->tariff}\n"
                                . "📌 Status: {$status}\n"
                                . "🕒 Sana: {$subscription->created_at->format('d.m.Y H:i')}";
                        }

                        $media[] = $mediaItem;
                    }

                    $request = Http::asMultipart();

                    foreach ($subscription->files as $index => $file) {

                        $filePath = storage_path(
                            'app/public/' . $file->file_path
                        );

                        if (!file_exists($filePath)) {
                            continue;
                        }

                        $request->attach(
                            'photo' . $index,
                            file_get_contents($filePath),
                            basename($filePath)
                        );
                    }

                    $request->post(
                        $this->apiUrl . 'sendMediaGroup',
                        [
                            'chat_id' => $chatId,
                            'media' => json_encode($media),
                        ]
                    );

                } else {

                    $text =
                        "📋 OBUNA MA'LUMOTI\n\n"
                        . "🆔 ID: {$subscription->id}\n"
                        . "👤 Ism: {$subscription->name}\n"
                        . "📞 Telefon: {$subscription->phone}\n"
                        . "💳 Tarif: {$subscription->tariff}\n"
                        . "📌 Status: {$status}\n"
                        . "🕒 Sana: {$subscription->created_at->format('d.m.Y H:i')}";

                    $this->sendMessage(
                        $chatId,
                        $text
                    );
                }
            }
        }

        if (
            isset($message['text']) &&
            $message['text'] === '/settings' &&
            $this->hasAdminAccess($chatId)
        ) {

            $setting = Setting::query()->first();

            if(!$setting){
                $setting = Setting::query()->create([
                    'standard_price' => 100000,
                    'premium_price' => 200000,
                ]);
            }

            $text =
                "⚙️ BOT SOZLAMALARI\n\n"
                . "💰 Standard: {$setting->standard_price}\n"
                . "💎 Premium: {$setting->premium_price}\n\n"
                . "💳 Karta: {$setting->card_number}\n"
                . "👤 Egasi: {$setting->card_holder}\n"
                . "   Guruh: {$setting->link}";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '✏️ Standard narx',
                            'callback_data' => 'edit_standard_price'
                        ]
                    ],
                    [
                        [
                            'text' => '✏️ Premium narx',
                            'callback_data' => 'edit_premium_price'
                        ]
                    ],
                    [
                        [
                            'text' => '✏️ Karta raqam',
                            'callback_data' => 'edit_card_number'
                        ]
                    ],
                    [
                        [
                            'text' => '✏️ Karta egasi',
                            'callback_data' => 'edit_card_holder'
                        ]
                    ],
                    [
                        [
                            'text' => '✏️ Link',
                            'callback_data' => 'edit_link'
                        ]
                    ]
                ]
            ];

            if ($this->isSuperAdmin($chatId)) {

                $keyboard['inline_keyboard'][] = [
                    [
                        'text' => '➕ Admin qo‘shish',
                        'callback_data' => 'add_admin'
                    ]
                ];

                $keyboard['inline_keyboard'][] = [
                    [
                        'text' => '❌ Adminni olib tashlash',
                        'callback_data' => 'show_delete_admins'
                    ]
                ];
            }

           if ($telegramState?->message_id) {

                Http::post($this->apiUrl . 'deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $telegramState->message_id,
                ]);
            }

            $response = Http::post($this->apiUrl . 'sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => json_encode($keyboard),
            ]);

            $messageId = $response['result']['message_id'];

            TelegramState::query()->updateOrCreate(
                [
                    'chat_id' => $chatId
                ],
                [
                    'message_id' => $messageId
                ]
            );
        }
        // contact kelganda
        if (isset($message['contact'])) {

            $phone = $message['contact']['phone_number'];

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '⭐ Standard',
                            'callback_data' => 'tariff_standard'
                        ],
                        [
                            'text' => '💎 Premium',
                            'callback_data' => 'tariff_premium'
                        ]
                    ]
                ]
            ];

            Subscription::query()->create([
                'telegram_id' => $chatId,
                'name' => $firstName . ' ' . $lastName,
                'phone' => $phone,
                'status' => 'pending',
            ]);

            $removeKeyboard = [
                'remove_keyboard' => true
            ];

            // oldingi contact keyboardni yopadi
            $this->sendMessage(
                $chatId,
                "Telefon raqamingiz qabul qilindi ✅",
                $removeKeyboard
            );



            // yangi tarif buttonlarini chiqaradi
            $this->sendMessage(
                $chatId,
                "Tarif tanlang:",
                $keyboard
            );
        }

        if(isset($message['photo']) || isset($message['document'])){

            $telegramFileId = null;

            // photo
            if (isset($message['photo'])) {

                $photo = end($message['photo']);

                $telegramFileId = $photo['file_id'];
            }

            // document
            if (isset($message['document'])) {

                $telegramFileId = $message['document']['file_id'];
            }

            // telegramdan file info olish
            $response = Http::get(
                $this->apiUrl . 'getFile',
                [
                    'file_id' => $telegramFileId
                ]
            );

            $filePath = $response['result']['file_path'];

            // file download url
            $fileUrl = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";

            // file content
            $fileContent = Http::get($fileUrl)->body();

            // filename
            $fileName = time() . '_' . basename($filePath);

            // storagega saqlash
            Storage::disk('public')->put(
                'checks/' . $fileName,
                $fileContent
            );

            // db update
            $subscription = Subscription::query()
                ->where('telegram_id', $chatId)
                ->latest()
                ->first();

            if(!$subscription){

                $this->sendMessage(
                    $chatId,
                    "Avval tarif tanlang."
                );

                return;
            }

            $lastFile = File::query()
                ->where('subscription_id', $subscription->id)
                ->latest()
                ->first();

            // approved bo'lsa qayta yuborolmaydi
            if($subscription->status === 'approved'){

                $setting = Setting::query()->first();

                $this->sendMessage(
                    $chatId,
                    "✅ Sizning obunangiz allaqachon tasdiqlangan.\n\n"
                    . "<a href='{$setting->link}'>📎 Guruhga kirish</a>"
                );

                return;
            }

            if($subscription->status === 'pending' && $lastFile){

                $this->sendMessage(
                    $chatId,
                    "⏳ Siz allaqachon chek yuborgansiz.\n\nAdmin tasdiqlashini kuting."
                );

                return;
            }

            if($subscription){

                File::query()->create([
                    'subscription_id' => $subscription->id,
                    'file_name' => $fileName,
                    'file_path' => 'checks/' . $fileName,
                    'status' => 'pending',
                ]);

                $subscription->update([
                    'status' => 'pending'
                ]);
            }

            $this->sendMessage(
                $chatId,
                "✅ To'lov cheki qabul qilindi.\n\nAdmin tasdiqlashini kuting."
            );

            $adminText =
                "🆕 Yangi zayavka!\n\n"
                . "👤 Ism: {$subscription->name}\n"
                . "📞 Telefon: {$subscription->phone}\n"
                . "💳 Tarif: {$subscription->tariff}";

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '❌ Bekor qilish',
                            'callback_data' => 'rejected_'.$subscription->id
                        ],
                        [
                            'text' => '✅ Tasdiqlash',
                            'callback_data' => 'approved_'.$subscription->id
                        ]
                    ]
                ]
            ];

            if (isset($message['photo'])) {

                $admins = Admin::query()->pluck('telegram_id')->toArray();

                $admins[] = $this->superAdminId;

                foreach ($admins as $adminId) {

                    Http::post($this->apiUrl . 'sendPhoto', [
                        'chat_id' => $adminId,
                        'photo' => $telegramFileId,
                        'caption' => $adminText,
                        'reply_markup' => json_encode($keyboard),
                    ]);
                }
            }

            if (isset($message['document'])) {

                $admins = Admin::query()->pluck('telegram_id')->toArray();

                $admins[] = $this->superAdminId;

                foreach ($admins as $adminId) {

                    Http::post($this->apiUrl . 'sendDocument', [
                        'chat_id' => $adminId,
                        'document' => $telegramFileId,
                        'caption' => $adminText,
                        'reply_markup' => json_encode($keyboard),
                    ]);
                }
            }
        }
    }

    private function handleCallback(array $callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data = $callback['data'];

        $dataParts = explode('_', $data);

        $action = $dataParts[0];
        $subscriptionId = $dataParts[1] ?? null;

        $subscription = Subscription::with('files')
            ->find($subscriptionId);

        if ($data === 'tariff_standard') {

            Subscription::query()
                ->where('telegram_id', $chatId)
                ->latest()
                ->first()
                ?->update(['tariff' => 'standard']);

            $setting = Setting::query()->first();

            $text =
                "💼 Siz *Standard* tarifni tanladingiz.\n\n"

                . "💰 Tarif narxi: *{$setting->standard_price} so'm*\n\n"

                . "💳 To'lov uchun karta:\n"
                . "`{$setting->card_number}`\n\n"

                . "👤 Karta egasi:\n"
                . "{$setting->card_holder}\n\n"

                . "📸 To'lov qilganingizdan so'ng\n"
                . "chek rasmini yuboring.";

            $this->editMessage($chatId, $messageId, $text);
        }

        if ($data === 'tariff_premium') {

            Subscription::query()
                ->where('telegram_id', $chatId)
                ->latest()
                ->first()
                ?->update(['tariff' => 'premium']);

            $setting = Setting::query()->first();

            $text =
                "💎 Siz *Premium* tarifni tanladingiz.\n\n"

                . "💰 Tarif narxi: *{$setting->premium_price} so'm*\n\n"

                . "💳 To'lov uchun karta:\n"
                . "`{$setting->card_number}`\n\n"

                . "👤 Karta egasi:\n"
                . "{$setting->card_holder}\n\n"

                . "📸 To'lov qilganingizdan so'ng\n"
                . "chek rasmini yuboring.";

            $this->editMessage($chatId, $messageId, $text);
        }

        if($action === 'approved' && $subscription){

            if ($subscription->status !== 'pending') {

                $this->editMessage(
                    $chatId,
                    $messageId,
                    "❌ Bu zayavka allaqachon ko‘rib chiqilgan."
                );

                return;
            }

            $subscription->update([
                'status' => 'approved'
            ]);
            $subscription->files()->update(['status' => 'approve']);

            $setting = Setting::query()->first();
            $this->sendMessage(
                $subscription->telegram_id,
                "✅ Sizning obunangiz tasdiqlandi.\n\n"
                . "{$setting->link}"
            );
            $caption =
                "🆕 Yangi zayavka!\n\n"
                . "👤 Ism: {$subscription->name}\n"
                . "📞 Telefon: {$subscription->phone}\n"
                . "💳 Tarif: {$subscription->tariff}\n\n"
                . "✅ TASDIQLANDI";

            $this->editCaption(
                $chatId,
                $messageId,
                $caption
            );
        }

        if($action === 'rejected' && $subscription){

            if ($subscription->status !== 'pending') {

                $this->editMessage(
                    $chatId,
                    $messageId,
                    "❌ Bu zayavka allaqachon ko‘rib chiqilgan."
                );

                return;
            }

            $subscription->update([
                'status' => 'rejected'
            ]);

            $subscription->files()->update(['status' => 'reject']);

            $this->sendMessage(
                $subscription->telegram_id,
                "❌ To'lov chekingiz rad etildi.\n\nIltimos qayta yuboring."
            );

            $caption =
                "🆕 Yangi zayavka!\n\n"
                . "👤 Ism: {$subscription->name}\n"
                . "📞 Telefon: {$subscription->phone}\n"
                . "💳 Tarif: {$subscription->tariff}\n\n"
                . "❌ RAD ETILDI";

            $this->editCaption(
                $chatId,
                $messageId,
                $caption
            );
        }

        if ($data === 'add_admin') {

            $keyboard = [
                'keyboard' => [
                    [
                        [
                            'text' => '👤 Foydalanuvchi tanlash',
                            'request_users' => [
                                'request_id' => 1,
                                'user_is_bot' => false,
                                'max_quantity' => 1
                            ]
                        ]
                    ]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ];

            $this->sendMessage(
                $chatId,
                "👤 Admin qilinadigan foydalanuvchini tanlang:",
                $keyboard
            );

            return;
        }

        if (str_starts_with($data, 'delete_admin_')) {

            $adminId = str_replace('delete_admin_', '', $data);

            $admin = Admin::query()->find($adminId);

            if (!$admin) {

                $this->sendMessage(
                    $chatId,
                    "❌ Admin topilmadi."
                );

                return;
            }

            $name = $admin->name;

            $admin->delete();

            $this->editMessage(
                $chatId,
                $messageId,
                "✅ {$name} adminlikdan olindi."
            );

            return;
        }

       if (str_starts_with($data, 'edit_')) {

            $field = str_replace('edit_', '', $data);

            TelegramState::query()->updateOrCreate(
                [
                    'chat_id' => $chatId
                ],
                [
                    'state' => $field
                ]
            );

            $fieldNames = [
                'standard_price' => 'Standard Tarif narx',
                'premium_price' => 'Premium Tarif narx',
                'card_number' => 'Karta raqam',
                'card_holder' => 'Karta egasi',
                'link' => 'Link',
            ];

            $keyboard = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => '⬅️ Orqaga',
                            'callback_data' => 'back_settings'
                        ]
                    ]
                ]
            ];

          if ($messageId) {

                Http::post($this->apiUrl . 'deleteMessage', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);
            }

            $response = Http::post($this->apiUrl . 'sendMessage', [
                'chat_id' => $chatId,
                'text' => "✏️ Yangi {$fieldNames[$field]} ni yuboring:",
                'reply_markup' => json_encode($keyboard),
            ]);

            $newMessageId = $response['result']['message_id'];

            TelegramState::query()->updateOrCreate(
                [
                    'chat_id' => $chatId
                ],
                [
                    'state' => $field,
                    'message_id' => $newMessageId
                ]
            );

        }

        if ($data === 'back_settings') {

                TelegramState::query()
                    ->where('chat_id', $chatId)
                    ->update([
                        'state' => null
                    ]);

                $setting = Setting::query()->first();

                if (!$setting) {

                    $setting = Setting::query()->create([
                        'standard_price' => 100000,
                        'premium_price' => 200000,
                    ]);
                }

                $text =
                    "⚙️ BOT SOZLAMALARI\n\n"
                    . "💰 Standard: {$setting->standard_price}\n"
                    . "💎 Premium: {$setting->premium_price}\n\n"
                    . "💳 Karta: {$setting->card_number}\n"
                    . "👤 Egasi: {$setting->card_holder}\n"
                    . "   Guruh: {$setting->link}";

                $keyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '✏️ Standard narx',
                                'callback_data' => 'edit_standard_price'
                            ]
                        ],
                        [
                            [
                                'text' => '✏️ Premium narx',
                                'callback_data' => 'edit_premium_price'
                            ]
                        ],
                        [
                            [
                                'text' => '✏️ Karta raqam',
                                'callback_data' => 'edit_card_number'
                            ]
                        ],
                        [
                            [
                                'text' => '✏️ Karta egasi',
                                'callback_data' => 'edit_card_holder'
                            ]
                        ],
                        [
                            [
                                'text' => '✏️ Link',
                                'callback_data' => 'edit_link'
                            ]
                        ]
                    ]
                ];

                if ($this->isSuperAdmin($chatId)) {

                    $keyboard['inline_keyboard'][] = [
                        [
                            'text' => '➕ Admin qo‘shish',
                            'callback_data' => 'add_admin'
                        ]
                    ];

                    $keyboard['inline_keyboard'][] = [
                        [
                            'text' => '❌ Adminni olib tashlash',
                            'callback_data' => 'show_delete_admins'
                        ]
                    ];
                }

               if ($messageId) {

                    Http::post($this->apiUrl . 'deleteMessage', [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ]);
                }

                $response = Http::post($this->apiUrl . 'sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'reply_markup' => json_encode($keyboard),
                ]);

                $newMessageId = $response['result']['message_id'];

                TelegramState::query()->updateOrCreate(
                    [
                        'chat_id' => $chatId
                    ],
                    [
                        'state' => null,
                        'message_id' => $newMessageId
                    ]
                );

                return;
            }

    }

    private function isSuperAdmin($chatId): bool
    {
        return (string)$chatId === (string)$this->superAdminId;
    }

    private function isAdmin($chatId): bool
    {
        return Admin::query()
            ->where('telegram_id', $chatId)
            ->exists();
    }

    private function hasAdminAccess($chatId): bool
    {
        return $this->isSuperAdmin($chatId) || $this->isAdmin($chatId);
    }

    public function sendMessage($chatId, $message, $replyMarkup = null)
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
        ];

        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post($this->apiUrl . 'sendMessage', $data);
    }

    public function editMessage($chatId, $messageId, $message)
    {
        Http::post($this->apiUrl . 'editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);
    }

    public function editCaption($chatId, $messageId, $caption)
    {
        Http::post($this->apiUrl . 'editMessageCaption', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'reply_markup' => json_encode([
                'inline_keyboard' => []
            ])
        ]);
    }
}

