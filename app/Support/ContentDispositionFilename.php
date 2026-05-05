<?php

namespace App\Support;

final class ContentDispositionFilename
{
    public static function fromHeader(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (preg_match("/filename\\*=([^']+)'[^']*'([^;]+)/i", $header, $matches) === 1) {
            return rawurldecode(trim($matches[2]));
        }

        if (preg_match('/filename\\*=UTF-8\\\'\\\'(.+)$/i', $header, $matches) === 1) {
            return rawurldecode(trim($matches[1]));
        }

        if (preg_match('/filename="((?:\\\\.|[^"\\\\])*)"/i', $header, $matches) === 1) {
            return stripcslashes($matches[1]);
        }

        if (preg_match('/filename=([^;\\r\\n]+)/i', $header, $matches) === 1) {
            return trim($matches[1], " \t\"'");
        }

        return null;
    }
}
