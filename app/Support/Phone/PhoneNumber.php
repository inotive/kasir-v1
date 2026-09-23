<?php

namespace App\Support\Phone;

class PhoneNumber
{
    public static function normalizeForMember(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $p = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($p, '0')) {
            return '62'.substr($p, 1);
        }

        if (! str_starts_with($p, '62')) {
            return '62'.$p;
        }

        return $p;
    }

    public static function toWhatsAppChatId(?string $phone): ?string
    {
        $normalized = self::normalizeForMember($phone);

        return $normalized ? $normalized.'@c.us' : null;
    }
}
