<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Models\UserStep;

class TelegramTrancekaBotController extends Controller
{
    private function validateEnvironmentVariables()
    {
        $requiredVars = [
            'TELEGRAM_TRANCEKA_BOT_TOKEN' => 'Telegram Bot Token is required for bot operation',
            'TELEGRAM_GROUP_CHAT_ID' => 'Group Chat ID is required for forwarding messages'
        ];

        $missing = [];
        foreach ($requiredVars as $var => $message) {
            if (empty(env($var))) {
                $missing[$var] = $message;
                Log::warning("Missing environment variable: {$var}", ['message' => $message]);
            }
        }

        if (!empty($missing)) {
            Log::error('Missing required environment variables', ['missing' => $missing]);
        }

        return empty($missing);
    }

    private array $countryFlags = [
        'Russia' => '🇷🇺',
        'Ukraine' => '🇺🇦',
        'Belarus' => '🇧🇾',
        'Turkey' => '🇹🇷',
        'Uzbekistan' => '🇺🇿',
        'Kazakhstan' => '🇰🇿',
        'Kyrgyzstan' => '🇰🇬',
        'Tajikistan' => '🇹🇯',
        'Turkmenistan' => '🇹🇲',
        'Afghanistan' => '🇦🇫'
    ];

    private function setUserLanguage($chatId)
    {
        try {
            $userStep = UserStep::where('chat_id', $chatId)->first();
            if ($userStep && $userStep->language) {
                Log::info('Setting user language', [
                    'chat_id' => $chatId,
                    'language' => $userStep->language
                ]);
                app()->setLocale($userStep->language);
                return $userStep->language;
            }
            // Default to English if no language is set
            app()->setLocale('en');
            return 'en';
        } catch (\Exception $e) {
            Log::error('Error setting user language', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Default to English on error
            app()->setLocale('en');
            return 'en';
        }
    }

    public function webhook(Request $request)
    {
        try {
            // Validate environment variables on each webhook request
            $this->validateEnvironmentVariables();

            // Log the webhook request with better structure
            Log::info('Webhook received', [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'has_callback_query' => isset($request->all()['callback_query']),
                'has_message' => isset($request->all()['message']),
                'has_my_chat_member' => isset($request->all()['my_chat_member'])
            ]);

            // More detailed payload logging for debugging
            Log::debug('Webhook payload details', [
                'payload' => $request->all()
            ]);

            $update = Telegram::getWebhookUpdate();

            // Handle my_chat_member updates
            if (isset($update['my_chat_member'])) {
                Log::info('My chat member update received', [
                    'chat_id' => $update['my_chat_member']['chat']['id'],
                    'new_status' => $update['my_chat_member']['new_chat_member']['status']
                ]);
                return response('ok');
            }

            // Get chat ID from either callback query or message
            $chatId = $update['callback_query']['message']['chat']['id'] ??
                ($update['message']['chat']['id'] ?? null);

            // Set language early if we have a chat ID
            if ($chatId) {
                $this->setUserLanguage($chatId);
                Log::info('Language set early in webhook', [
                    'chat_id' => $chatId,
                    'language' => app()->getLocale()
                ]);
            }

            // Explicitly check for callback_query first
            if ($update['callback_query'] ?? null) {
                Log::info('Callback query received', ['callback' => $update['callback_query']]);
                return $this->handleCallback($update);
            }

            if ($update->getMessage()) {
                Log::info('Message received', ['message' => $update->getMessage()]);
                return $this->handleMessage($update);
            }

            return response('ok');
        } catch (\Exception $e) {
            Log::error('Error in webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('error', 500);
        }
    }


    private function handleMessage($update)
    {
        try {
            $message = $update->getMessage();
            if (!$message) {
                Log::info('No message in update', ['update' => $update]);
                return response('ok');
            }

            $chatId = $message['chat']['id'];

            // Get or create user step first
            $userStep = UserStep::firstOrCreate(
                ['chat_id' => $chatId],
                ['step' => 'start']
            );

            // Set language immediately
            if ($userStep->language) {
                app()->setLocale($userStep->language);
            } else {
                // Default to English if no language is set
                app()->setLocale('en');
            }

            Log::info('Processing message', [
                'chat_id' => $chatId,
                'message' => $message,
                'language' => app()->getLocale()
            ]);

            // Handle /start command
            if (isset($message['text']) && $message['text'] === '/start') {
                UserStep::updateOrCreate(
                    ['chat_id' => $chatId],
                    ['step' => 'awaiting_language']
                );
                return $this->sendLanguageSelection($chatId);
            }

            // Handle contact sharing
            if (isset($message['contact'])) {
                return $this->handleContactShare($chatId, $message['contact'], $userStep);
            }

            // Handle user steps
            switch ($userStep->step) {
                case 'awaiting_from_city':
                    // Validate city name
                    $cityValidation = $this->validateCityName($message['text'] ?? '');
                    if (!$cityValidation['valid']) {
                        Log::warning('Invalid city name', [
                            'city' => $message['text'] ?? '',
                            'reason' => $cityValidation['reason'],
                            'chat_id' => $chatId
                        ]);
                        return $this->sendMessage($chatId, __('messages.enter_valid_city', [
                            'default' => 'Please enter a valid city name (2-50 characters, no special symbols).',
                            'reason' => $cityValidation['reason']
                        ]));
                    }

                    $userStep->update([
                        'from_city' => $cityValidation['sanitized'],
                        'step' => 'awaiting_to_country'
                    ]);
                    return $this->sendToCountrySelection($chatId);

                case 'awaiting_to_city':
                    // Validate destination city name
                    $cityValidation = $this->validateCityName($message['text'] ?? '');
                    if (!$cityValidation['valid']) {
                        Log::warning('Invalid destination city name', [
                            'city' => $message['text'] ?? '',
                            'reason' => $cityValidation['reason'],
                            'chat_id' => $chatId
                        ]);
                        return $this->sendMessage($chatId, __('messages.enter_valid_city', [
                            'default' => 'Please enter a valid city name (2-50 characters, no special symbols).',
                            'reason' => $cityValidation['reason']
                        ]));
                    }
                    $userStep->update([
                        'to_city' => $cityValidation['sanitized'],
                        'step' => 'awaiting_transport_type'
                    ]);
                    return $this->sendTransportTypeSelection($chatId);

                case 'awaiting_temperature':
                    // Validate temperature range
                    if (!isset($message['text']) || trim($message['text']) === '') {
                        return $this->sendMessage($chatId, __('messages.enter_valid_temperature'));
                    }

                    $temperature = trim($message['text']);
                    if (strlen($temperature) > 50) {
                        return $this->sendMessage($chatId, __('messages.enter_valid_temperature'));
                    }

                    $userStep->update([
                        'temperature_range' => $temperature,
                        'step' => 'awaiting_pallet_type'
                    ]);
                    return $this->sendPalletTypeSelection($chatId);

                case 'awaiting_pallet_count':
                    if (!isset($message['text']) || !is_numeric($message['text'])) {
                        return $this->sendMessage($chatId, __('messages.enter_valid_number'));
                    }
                    $userStep->update([
                        'pallet_count' => $message['text'],
                        'step' => 'awaiting_gross_weight'
                    ]);
                    return $this->sendGrossWeightRequest($chatId);

                case 'awaiting_custom_size':
                    $userStep->update([
                        'custom_size' => $message['text'],
                        'step' => 'awaiting_pallet_count' // O'zgartirildi: gross_weight o'rniga pallet_count
                    ]);
                    return $this->sendMessage($chatId, __('messages.enter_pallet_count'));

                case 'awaiting_pallet_size':
                    $userStep->update([
                        'pallet_size' => $message['text'],
                        'step' => 'awaiting_gross_weight'
                    ]);
                    return $this->sendGrossWeightRequest($chatId);

                case 'awaiting_gross_weight':
                    // Validate gross weight
                    if (!isset($message['text']) || !is_numeric($message['text']) || $message['text'] <= 0) {
                        return $this->sendMessage($chatId, __('messages.enter_valid_weight'));
                    }
                    $userStep->update([
                        'gross_weight' => $message['text'],
                        'step' => 'awaiting_contact'
                    ]);
                    return $this->requestContact($chatId);

                default:
                    return response('ok');
            }
        } catch (\Exception $e) {
            Log::error('Error handling message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('error', 500);
        }
    }
    private function sendGrossWeightRequest($chatId)
    {
        try {
            $this->setUserLanguage($chatId);

            Log::info('Sending gross weight request', [
                'chat_id' => $chatId,
                'language' => app()->getLocale()
            ]);

            return $this->sendMessage($chatId, __('messages.enter_gross_weight'));
        } catch (\Exception $e) {
            Log::error('Error in sendGrossWeightRequest', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleCallback($update)
    {
        try {
            if (!isset($update['callback_query'])) {
                Log::warning('Invalid callback update received');
                return response('Invalid callback', 400);
            }

            $callback = $update['callback_query'];
            $chatId = $callback['message']['chat']['id'] ?? null;
            $data = $callback['data'] ?? null;
            $callbackId = $callback['id'] ?? null;

            if (!$chatId || !$data) {
                Log::error('Missing required callback data', [
                    'chat_id' => $chatId,
                    'data' => $data
                ]);
                return response('Invalid callback data', 400);
            }

            // Set language before processing
            $this->setUserLanguage($chatId);

            Log::info('Processing callback', [
                'chat_id' => $chatId,
                'data' => $data,
                'callback_id' => $callbackId
            ]);

            // Answer callback query first
            if ($callbackId) {
                try {
                    Telegram::answerCallbackQuery([
                        'callback_query_id' => $callbackId
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to answer callback query', [
                        'callback_id' => $callbackId,
                        'error' => $e->getMessage()
                    ]);
                    // Continue processing despite the error
                }
            }

            $userStep = UserStep::where('chat_id', $chatId)->first();
            if (!$userStep) {
                Log::error('User step not found', ['chat_id' => $chatId]);
                return response('error', 404);
            }

            // Handle language selection
            if (strpos($data, 'lang:') === 0) {
                $language = str_replace('lang:', '', $data);
                return $this->handleLanguageSelection($chatId, $language);
            }

            // Handle country selections
            if (strpos($data, 'from:') === 0) {
                $country = str_replace('from:', '', $data);
                $countryKey = strtolower($country);
                $translatedCountry = __("messages.country.{$countryKey}");

                $userStep->update([
                    'from_country' => $country,
                    'step' => 'awaiting_from_city'
                ]);

                return $this->sendMessage($chatId, __('messages.enter_city', [
                    'country' => $translatedCountry,
                    'flag' => $this->countryFlags[$country]
                ]));
            }

            if (strpos($data, 'to:') === 0) {
                $country = str_replace('to:', '', $data);
                $countryKey = strtolower($country);
                $translatedCountry = __("messages.country.{$countryKey}");

                $userStep->update([
                    'to_country' => $country,
                    'step' => 'awaiting_to_city'
                ]);

                return $this->sendMessage($chatId, __('messages.enter_city', [
                    'country' => $translatedCountry,
                    'flag' => $this->countryFlags[$country]
                ]));
            }

            // Handle transport type selection
            if (strpos($data, 'ref:') === 0) {
                try {
                    $type = str_replace('ref:', '', $data);

                    Log::info('Ref transport type selected', [
                        'chat_id' => $chatId,
                        'type' => $type
                    ]);

                    $userStep->update([
                        'transport_type' => $type,
                        'step' => 'awaiting_ref_mode'
                    ]);

                    return $this->sendRefModeSelection($chatId);
                } catch (\Exception $e) {
                    Log::error('Error in ref type selection', [
                        'error' => $e->getMessage(),
                        'chat_id' => $chatId
                    ]);
                    throw $e;
                }
            }

            // Handle ref mode selection
            if (strpos($data, 'mode:') === 0) {
                try {
                    $mode = str_replace('mode:', '', $data);

                    Log::info('Ref mode selected', [
                        'chat_id' => $chatId,
                        'mode' => $mode
                    ]);

                    $userStep->update([
                        'ref_mode' => $mode,
                        'step' => 'awaiting_temperature' // Haroratni kiritish uchun yangi step
                    ]);

                    // Harorat oraligini kiritishni sorash
                    return $this->sendMessage($chatId, __('messages.enter_temperature_range'));
                } catch (\Exception $e) {
                    Log::error('Error in mode selection', [
                        'error' => $e->getMessage(),
                        'chat_id' => $chatId
                    ]);
                    throw $e;
                }
            }

            if (strpos($data, 'tent:') === 0) {
                try {
                    $type = str_replace('tent:', '', $data);

                    Log::info('Tent transport type selected', [
                        'chat_id' => $chatId,
                        'type' => $type
                    ]);

                    $userStep->update([
                        'transport_type' => $type,
                        'step' => 'awaiting_pallet_type'
                    ]);

                    return $this->sendPalletTypeSelection($chatId);
                } catch (\Exception $e) {
                    Log::error('Error in tent type selection', [
                        'error' => $e->getMessage(),
                        'chat_id' => $chatId
                    ]);
                    throw $e;
                }
            }

            if (strpos($data, 'pallet:') === 0) {
                try {
                    $type = str_replace('pallet:', '', $data);

                    Log::info('Pallet type selected', [
                        'chat_id' => $chatId,
                        'type' => $type
                    ]);

                    $palletSizes = [
                        'american' => '120x120',
                        'euro' => '120x80',
                        'finnish' => '120x100',
                        'no_standard' => null
                    ];

                    $userStep->update([
                        'pallet_type' => $type,
                        'pallet_size' => $palletSizes[$type] ?? null,
                        'step' => $type === 'no_standard' ? 'awaiting_custom_size' : 'awaiting_pallet_count'
                    ]);

                    return $type === 'no_standard'
                        ? $this->sendMessage($chatId, __('messages.enter_custom_size'))
                        : $this->sendMessage($chatId, __('messages.enter_pallet_count'));
                } catch (\Exception $e) {
                    Log::error('Error in pallet type selection', [
                        'error' => $e->getMessage(),
                        'chat_id' => $chatId
                    ]);
                    throw $e;
                }
            }

            // If we reached here, this is an unhandled callback type
            Log::warning('Unhandled callback type', [
                'data' => $data,
                'chat_id' => $chatId
            ]);

            return response('ok');
        } catch (\Exception $e) {
            Log::error('Error in callback handling', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('error', 500);
        }
    }

    private function handleLanguageSelection($chatId, $language)
    {
        try {
            if (empty($chatId)) {
                Log::error('Empty chat ID in language selection');
                return response('Invalid chat ID', 400);
            }

            Log::info('Language selection started', [
                'chat_id' => $chatId,
                'language' => $language
            ]);

            // Validate language code more strictly
            $validLanguages = ['en', 'ru', 'uz'];
            if (!in_array($language, $validLanguages)) {
                Log::warning('Invalid language selection', [
                    'chat_id' => $chatId,
                    'language' => $language,
                    'valid_languages' => $validLanguages
                ]);
                return $this->sendLanguageSelection($chatId);
            }

            // Set language immediately
            app()->setLocale($language);

            // Update or create user with language preference in a transaction
            \DB::beginTransaction();
            try {
                // check if a user already exists
                $userExists = UserStep::where('chat_id', $chatId)->exists();

                UserStep::updateOrCreate(
                    ['chat_id' => $chatId],
                    [
                        'language' => $language,
                        'step' => 'awaiting_from_country'
                    ]
                );
                \DB::commit();

                Log::info('User language updated', [
                    'chat_id' => $chatId,
                    'language' => $language,
                    'user_existed' => $userExists
                ]);
            } catch (\Exception $e) {
                \DB::rollBack();
                Log::error('Failed to update user language in DB', [
                    'chat_id' => $chatId,
                    'language' => $language,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Try a simplified update without transaction as fallback
                try {
                    UserStep::where('chat_id', $chatId)->update(['language' => $language]);
                    Log::info('Used fallback method to update language', ['chat_id' => $chatId]);
                } catch (\Exception $fallbackEx) {
                    Log::error('Fallback language update also failed', [
                        'error' => $fallbackEx->getMessage()
                    ]);
                }

                // Don't throw exception - try to continue
            }

            // Try/catch block to ensure robustness in confirmation message
            try {
                // Send confirmation in selected language
                $this->sendMessage($chatId, __('messages.language_selected'));

                // Small delay to ensure messages are sent in order
                usleep(300000); // 300ms delay - further increased for reliability

                // Then send country selection in the selected language
                return $this->sendFromCountrySelection($chatId);
            } catch (\Exception $confirmException) {
                Log::error('Error sending confirmation after language selection', [
                    'chat_id' => $chatId,
                    'language' => $language,
                    'error' => $confirmException->getMessage(),
                    'trace' => $confirmException->getTraceAsString()
                ]);

                // Wait a bit longer before retry
                usleep(500000); // 500ms delay

                // Try one more time to send country selection with a direct Telegram API call
                try {
                    return $this->sendFromCountrySelection($chatId);
                } catch (\Exception $retryEx) {
                    Log::error('Retry also failed', [
                        'error' => $retryEx->getMessage()
                    ]);

                    // Send a simplified fallback message
                    return $this->sendMessage($chatId, 'Please select your country:');
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in handleLanguageSelection', [
                'chat_id' => $chatId,
                'language' => $language,
                'message' => $e->getMessage()
            ]);

            // Try to send error message to user using our sendMessage method
            try {
                $this->sendMessage($chatId, 'Sorry, there was an error processing your language selection. Please try /start again.');
            } catch (\Exception $innerE) {
                Log::error('Error sending error message to user', [
                    'message' => $innerE->getMessage()
                ]);
            }

            throw $e;
        }
    }

    private function sendLanguageSelection($chatId)
    {
        try {
            // Set each language and get welcome message and button text
            app()->setLocale('en');
            $welcomeEn = __('messages.welcome');
            $btnEn = __('messages.btn_english');

            app()->setLocale('ru');
            $welcomeRu = __('messages.welcome');
            $btnRu = __('messages.btn_russian');

            app()->setLocale('uz');
            $welcomeUz = __('messages.welcome');
            $btnUz = __('messages.btn_uzbek');

            // Send multilingual welcome message with properly translated buttons
            return $this->sendMessage($chatId,
                $welcomeEn . "\n\n" . $welcomeRu . "\n\n" . $welcomeUz,
                [
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => $btnEn, 'callback_data' => 'lang:en'],
                                ['text' => $btnRu, 'callback_data' => 'lang:ru'],
                                ['text' => $btnUz, 'callback_data' => 'lang:uz']
                            ]
                        ]
                    ])
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error in sendLanguageSelection', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendFromCountrySelection($chatId)
    {
        try {
            $this->setUserLanguage($chatId);

            $countries = [];
            foreach ($this->countryFlags as $country => $flag) {
                $countryKey = strtolower($country);
                $translatedName = __("messages.country.{$countryKey}");
                $countries[] = [ ['text' => "{$translatedName} {$flag}", 'callback_data' => "from:{$country}"] ];
            }

            return $this->sendMessage($chatId, __('messages.select_origin_country'), [
                'reply_markup' => json_encode([
                    'inline_keyboard' => $countries
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending country selection', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendToCountrySelection($chatId)
    {
        try {
            $this->setUserLanguage($chatId);

            $countries = [];
            foreach ($this->countryFlags as $country => $flag) {
                $countryKey = strtolower($country);
                $translatedName = __("messages.country.{$countryKey}");
                $countries[] = [ ['text' => "{$translatedName} {$flag}", 'callback_data' => "to:{$country}"] ];
            }

            return $this->sendMessage($chatId, __('messages.select_destination_country'), [
                'reply_markup' => json_encode([
                    'inline_keyboard' => $countries
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending destination country selection', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendTransportTypeSelection($chatId)
    {
        try {
            $this->setUserLanguage($chatId);

            Log::info('Sending transport type selection', [
                'chat_id' => $chatId,
                'language' => app()->getLocale()
            ]);

            return $this->sendMessage($chatId, __('messages.select_transport_type'), [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => __('messages.btn_ref'),
                                'callback_data' => 'ref:ref'
                            ]
                        ],
                        [
                            [
                                'text' => __('messages.btn_tent'),
                                'callback_data' => 'tent:tent'
                            ]
                        ]
                    ]
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sendTransportTypeSelection', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendRefModeSelection($chatId)
    {
        try {
            $this->setUserLanguage($chatId);

            Log::info('Sending ref mode selection', [
                'chat_id' => $chatId,
                'language' => app()->getLocale()
            ]);

            return $this->sendMessage($chatId, __('messages.select_ref_mode'), [
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => __('messages.btn_s_mode'),
                                'callback_data' => 'mode:s_mode'
                            ]
                        ],
                        [
                            [
                                'text' => __('messages.btn_bez_mode'),
                                'callback_data' => 'mode:bez_mode'
                            ]
                        ]
                    ]
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error in ref mode selection', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function sendPalletTypeSelection($chatId)
    {
        $this->setUserLanguage($chatId);  // Ensure correct language is set
        return $this->sendMessage($chatId, __('messages.select_pallet_type'), [
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => __('messages.pallet.american'),
                            'callback_data' => 'pallet:american'
                        ]
                    ],
                    [
                        [
                            'text' => __('messages.pallet.finnish'),
                            'callback_data' => 'pallet:finnish'
                        ]
                    ],
                    [
                        [
                            'text' => __('messages.pallet.euro'),
                            'callback_data' => 'pallet:euro'
                        ]
                    ],
                    [
                        [
                            'text' => __('messages.pallet.no_standard'),
                            'callback_data' => 'pallet:no_standard'
                        ]
                    ]
                ]
            ])
        ]);
    }

    private function requestContact($chatId)
    {
        try {
            $this->setUserLanguage($chatId);
            return $this->sendMessage($chatId, __('messages.share_contact'), [
                'reply_markup' => json_encode([
                    'keyboard' => [
                        [
                            [
                                'text' => __('messages.btn_share_contact'),
                                'request_contact' => true
                            ]
                        ]
                    ],
                    'resize_keyboard' => true,
                    'one_time_keyboard' => true
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error in requestContact', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleContactShare($chatId, $contact, UserStep $userStep)
    {
        try {
            // Set language before processing
            $this->setUserLanguage($chatId);

            // Validate and process contact info
            $phoneNumber = $contact['phone_number'] ?? '';
            $firstName = $contact['first_name'] ?? '';
            $lastName = $contact['last_name'] ?? '';

            $contactName = trim($firstName . ' ' . $lastName);

            if (empty($phoneNumber)) {
                return $this->sendMessage($chatId, __('messages.invalid_contact'));
            }

            // Update user step
            $userStep->update([
                'contact_phone' => $phoneNumber,
                'contact_name' => $contactName,
                'step' => 'completed'
            ]);

            // Create summary message
            $summaryMessage = $userStep->getFullDetails();

            // Send summary to user
            $this->sendMessage($chatId, $summaryMessage);

            // Forward to group chat
            try {
                $groupChatId = -1002605313790;
                if ($groupChatId) {
                    Log::info('Attempting to forward transport request to group', [
                        'group_chat_id' => $groupChatId,
                        'chat_id' => $chatId
                    ]);

                    // Create group message with proper translation
                    $groupMessage = __('messages.new_transport_request') . "\n\n" .
                        $userStep->getFullDetails();

                    // Send message to group
                    $response = $this->sendMessage($groupChatId, $groupMessage);
                    Log::info('Successfully forwarded transport request to group', [
                        'group_chat_id' => $groupChatId,
                        'response' => $response
                    ]);
                } else {
                    Log::warning('TELEGRAM_GROUP_CHAT_ID is not set in .env file', [
                        'group_chat_id' => $groupChatId
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error forwarding transport request to group', [
                    'group_chat_id' => $groupChatId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Don't throw the exception to avoid affecting user experience
            }

            // Send completion message in user's language
            return $this->sendMessage($chatId, __('messages.request_completed'), [
                'reply_markup' => json_encode([
                    'remove_keyboard' => true
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling contact share', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendMessage($chatId, $text, $extra = [])
    {
        try {
            if (empty($chatId)) {
                Log::error('Attempted to send message with empty chat ID');
                return null;
            }

            // Always set language before sending any message
            $userStep = UserStep::where('chat_id', $chatId)->first();
            if ($userStep && $userStep->language) {
                app()->setLocale($userStep->language);
                Log::info('Language set for message', [
                    'chat_id' => $chatId,
                    'language' => $userStep->language
                ]);
            }

            Log::info('Sending message', [
                'chat_id' => $chatId,
                'text' => $text,
                'language' => app()->getLocale()
            ]);

            $messageData = array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML'
            ], $extra);

            return Telegram::sendMessage($messageData);
        } catch (\Exception $e) {
            Log::error('Error sending message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function validateCityName($cityName)
    {
        try {
            $result = [
                'valid' => false,
                'reason' => '',
                'sanitized' => ''
            ];

            // Check if city name is provided
            if (!isset($cityName) || trim($cityName) === '') {
                $result['reason'] = __('messages.enter_valid_city');
                return $result;
            }

            // Sanitize the city name - remove extra spaces and special characters
            $sanitized = trim($cityName);

            // Basic length validation
            if (strlen($sanitized) < 2) {
                $result['reason'] = __('messages.enter_valid_city');
                return $result;
            }

            if (strlen($sanitized) > 50) {
                $result['reason'] = __('messages.enter_valid_city');
                $sanitized = substr($sanitized, 0, 50);
            }

            // Check for potentially dangerous input
            if (preg_match('/[<>&;#\/]/', $sanitized)) {
                // Remove potentially harmful characters
                $sanitized = preg_replace('/[<>&;#\/]/', '', $sanitized);
                $result['reason'] = __('messages.enter_valid_city');

                // If after sanitization it's too short, reject it
                if (strlen($sanitized) < 2) {
                    return $result;
                }
            }

            // City name is valid after sanitization
            $result['valid'] = true;
            $result['sanitized'] = $sanitized;
            return $result;
        } catch (\Exception $e) {
            Log::error('Error validating city name', [
                'city' => $cityName,
                'error' => $e->getMessage()
            ]);
            return [
                'valid' => false,
                'reason' => __('messages.enter_valid_city'),
                'sanitized' => ''
            ];
        }
    }
}
