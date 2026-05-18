<?php

namespace App\Support;

use Carbon\Carbon;

final class MongolianRegistryDemographics
{
    /**
     * @return array{gender: string, age: int}|null
     */
    public static function parse(string $registry, mixed $referenceDate = null): ?array
    {
        $normalized = mb_strtoupper(trim($registry), 'UTF-8');
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/^[А-ЯӨҮЁ]{2}(\d{8})$/u', $normalized, $matches)) {
            $digits = $matches[1];
            $genderDigit = (int) substr($digits, -1);

            return self::parseBirthDateFromYyMmDd(
                (int) substr($digits, 0, 2),
                (int) substr($digits, 2, 2),
                (int) substr($digits, 4, 2),
                $genderDigit,
                $referenceDate
            );
        }

        $digitsOnly = preg_replace('/\D+/', '', $normalized);
        if (! is_string($digitsOnly) || strlen($digitsOnly) < 8) {
            return null;
        }

        $datePart = substr($digitsOnly, 0, 6);
        $yy = (int) substr($datePart, 0, 2);
        $mmRaw = (int) substr($datePart, 2, 2);
        $dd = (int) substr($datePart, 4, 2);
        $mm = $mmRaw;
        $fullYear = 1900 + $yy;
        if ($mmRaw > 20) {
            $mm = $mmRaw - 20;
            $fullYear = 2000 + $yy;
        }

        $genderDigit = strlen($digitsOnly) >= 8
            ? (int) substr($digitsOnly, 7, 1)
            : (int) substr($digitsOnly, -1);

        if (! checkdate($mm, $dd, $fullYear)) {
            return null;
        }

        $reference = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : now()->startOfDay();
        $birthDate = Carbon::create($fullYear, $mm, $dd, 0, 0, 0, $reference->timezone)->startOfDay();
        $age = (int) $birthDate->diffInYears($reference);
        $gender = $genderDigit % 2 === 0 ? 'Эмэгтэй' : 'Эрэгтэй';

        return [
            'gender' => $gender,
            'age' => $age,
        ];
    }

    /**
     * @return array{gender: string, age: int}|null
     */
    private static function parseBirthDateFromYyMmDd(int $yy, int $mm, int $dd, int $genderDigit, mixed $referenceDate): ?array
    {
        if ($mm < 1 || $mm > 12 || $dd < 1 || $dd > 31) {
            return null;
        }

        $reference = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : now()->startOfDay();
        $century = $yy <= ((int) $reference->format('y')) ? 2000 : 1900;
        $fullYear = $century + $yy;

        if (! checkdate($mm, $dd, $fullYear)) {
            return null;
        }

        $birthDate = Carbon::create($fullYear, $mm, $dd, 0, 0, 0, $reference->timezone)->startOfDay();
        $age = (int) $birthDate->diffInYears($reference);
        $gender = $genderDigit % 2 === 0 ? 'Эмэгтэй' : 'Эрэгтэй';

        return [
            'gender' => $gender,
            'age' => $age,
        ];
    }

    /**
     * @return array{gender: 'female'|'male', age_bucket: string, age: int}|null
     */
    public static function parseForStatistics(string $registry, mixed $referenceDate = null): ?array
    {
        $parsed = self::parse($registry, $referenceDate);
        if ($parsed === null) {
            return null;
        }

        $age = $parsed['age'];
        $ageBucket = match (true) {
            $age >= 14 && $age <= 15 => '14_15',
            $age >= 16 && $age <= 17 => '16_17',
            $age >= 18 && $age <= 21 => '18_21',
            $age >= 22 && $age <= 29 => '22_29',
            $age >= 30 && $age <= 34 => '30_34',
            $age >= 35 => '35_plus',
            default => null,
        };
        if ($ageBucket === null) {
            return null;
        }

        return [
            'gender' => $parsed['gender'] === 'Эмэгтэй' ? 'female' : 'male',
            'age_bucket' => $ageBucket,
            'age' => $age,
        ];
    }
}
