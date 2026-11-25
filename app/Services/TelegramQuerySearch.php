<?php

namespace App\Services;

use danog\MadelineProto\API;

/**
 * Telegram global qidiruvi (messages.searchGlobal) aniq emas: "EGS21720" so'ralsa,
 * "EGS21725" kabi o'xshash yozuvlarni ham qaytaradi, javobdagi `count` esa shu
 * noaniq natijalarning taxminiy soni. Shu sabab xabarlarni o'zimiz olib,
 * faqat ID aynan (to'liq so'z sifatida) uchragan xabarlarni sanaymiz.
 */
class TelegramQuerySearch
{
    private const PAGE_LIMIT = 100;
    private const MAX_PAGES = 10;

    public static function countExact(API $madeline, string $customId): int
    {
        $count = 0;
        $params = ['q' => $customId, 'limit' => self::PAGE_LIMIT];

        for ($page = 0; $page < self::MAX_PAGES; $page++) {
            $results = $madeline->messages->searchGlobal($params);
            $messages = $results['messages'] ?? [];

            foreach ($messages as $message) {
                if (self::containsExact($message['message'] ?? '', $customId)) {
                    $count++;
                }
            }

            // messages.messages — hammasi bitta javobda keldi; messagesSlice — next_rate bo'lsa davomi bor
            if (count($messages) < self::PAGE_LIMIT || empty($results['next_rate'])) {
                break;
            }

            $last = end($messages);
            $params['offset_rate'] = $results['next_rate'];
            $params['offset_peer'] = $last['peer_id'];
            $params['offset_id'] = $last['id'];

            usleep(300000);
        }

        return $count;
    }

    /** "EGS21720" ni topadi, lekin "EGS217201" yoki "XEGS21720" ichidan emas. */
    public static function containsExact(string $text, string $customId): bool
    {
        if ($text === '' || $customId === '') {
            return false;
        }

        $pattern = '/(?<![\p{L}\p{N}])' . preg_quote($customId, '/') . '(?![\p{L}\p{N}])/iu';

        return preg_match($pattern, $text) === 1;
    }
}
