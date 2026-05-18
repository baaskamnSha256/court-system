<?php

namespace App\Support;

final class DefendantRegistryResolver
{
    public static function registryForSentence(array $sentence, mixed $hearing, int $sentenceIndex = 0): string
    {
        $fromSentence = mb_strtoupper(trim((string) ($sentence['defendant_registry'] ?? '')), 'UTF-8');
        if ($fromSentence !== '') {
            return $fromSentence;
        }

        $name = trim((string) ($sentence['defendant_name'] ?? ''));
        $names = self::namesFromHearing($hearing);
        $registries = self::registriesFromHearing($hearing);

        if ($name !== '') {
            foreach ($names as $index => $hearingName) {
                if (trim((string) $hearingName) === $name) {
                    return $registries[$index] ?? '';
                }
            }
        }

        return $registries[$sentenceIndex] ?? '';
    }

    /**
     * @return list<string>
     */
    public static function namesFromHearing(mixed $hearing): array
    {
        if (is_array($hearing->defendant_names ?? null)) {
            return collect($hearing->defendant_names)
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn (string $name) => $name !== '')
                ->values()
                ->all();
        }

        return collect(preg_split('/[\n,]+/u', (string) ($hearing->defendants ?? '')) ?: [])
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function registriesFromHearing(mixed $hearing): array
    {
        if (! is_array($hearing->defendant_registries ?? null)) {
            return [];
        }

        return collect($hearing->defendant_registries)
            ->map(fn ($registry) => mb_strtoupper(trim((string) $registry), 'UTF-8'))
            ->values()
            ->all();
    }
}
