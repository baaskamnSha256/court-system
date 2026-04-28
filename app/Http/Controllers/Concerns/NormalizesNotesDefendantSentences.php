<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Validation\ValidationException;

trait NormalizesNotesDefendantSentences
{
    private const OUTCOME_TRACK_SENTENCE = 'sentence';

    private const OUTCOME_TRACK_NO_SENTENCE = 'no_sentence';

    private const OUTCOME_TRACK_TERMINATION = 'termination';

    private const TERMINATION_DISMISS = 'dismiss';

    private const TERMINATION_ACQUIT = 'acquit';

    /**
     * @param  array<int, mixed>  $sentences
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeDefendantSentences(array $sentences): array
    {
        $result = [];
        foreach ($sentences as $index => $sentence) {
            if (! is_array($sentence)) {
                continue;
            }

            $rawOutcomeTrack = is_string($sentence['outcome_track'] ?? null) ? trim($sentence['outcome_track']) : '';
            $explicitSentenceTrack = $rawOutcomeTrack === self::OUTCOME_TRACK_SENTENCE;
            $specialOutcomeRaw = trim((string) ($sentence['special_outcome'] ?? ''));
            if (
                $specialOutcomeRaw !== ''
                && in_array($specialOutcomeRaw, static::specialOutcomeOptionValues(), true)
                && $this->rawDefendantSentenceHasSentencingPayload($sentence)
                && ! $explicitSentenceTrack
            ) {
                throw ValidationException::withMessages([
                    "notes_defendant_sentences.{$index}.special_outcome" => 'Тусгай шийдвэр болон ялын мэдээллийг хамтад нь оруулж болохгүй.',
                ]);
            }

            $defendantName = trim((string) ($sentence['defendant_name'] ?? ''));
            $defendantRegistry = mb_strtoupper(trim((string) ($sentence['defendant_registry'] ?? '')), 'UTF-8');
            $outcomeTrack = $this->normalizeOutcomeTrack($sentence['outcome_track'] ?? null, $sentence);

            $specialOutcome = trim((string) ($sentence['special_outcome'] ?? ''));
            if (! in_array($specialOutcome, static::specialOutcomeOptionValues(), true)) {
                $specialOutcome = '';
            }

            $terminationKind = trim((string) ($sentence['termination_kind'] ?? ''));
            if (! in_array($terminationKind, [self::TERMINATION_DISMISS, self::TERMINATION_ACQUIT], true)) {
                $terminationKind = '';
            }
            $terminationNote = trim((string) ($sentence['termination_note'] ?? ''));

            $decidedMatterIds = array_values(array_filter(array_map('intval', (array) ($sentence['decided_matter_ids'] ?? []))));
            $punishmentsRaw = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
            $punishments = $this->normalizePunishments($punishmentsRaw, true);

            if ($outcomeTrack === self::OUTCOME_TRACK_NO_SENTENCE) {
                $punishments = [];
                $sentence['allocations'] = [];
                $terminationKind = '';
                $terminationNote = '';
            } elseif ($outcomeTrack === self::OUTCOME_TRACK_TERMINATION) {
                $punishments = [];
                $sentence['allocations'] = [];
                $specialOutcome = '';
            } elseif ($outcomeTrack === self::OUTCOME_TRACK_SENTENCE) {
                $specialOutcome = '';
                $terminationKind = '';
                $terminationNote = '';
            }

            $allocations = $this->normalizeSentenceAllocations(
                is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [],
                $decidedMatterIds,
                $punishments
            );
            if (empty($decidedMatterIds) && ! empty($allocations)) {
                $decidedMatterIds = collect($allocations)
                    ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
            }

            if (
                $defendantName === ''
                && $defendantRegistry === ''
                && $specialOutcome === ''
                && $terminationKind === ''
                && $terminationNote === ''
                && empty($decidedMatterIds)
                && empty($punishments)
                && empty($allocations)
            ) {
                continue;
            }

            $result[] = [
                'defendant_name' => $defendantName,
                'defendant_registry' => $defendantRegistry,
                'outcome_track' => $outcomeTrack,
                'termination_kind' => $terminationKind,
                'termination_note' => $terminationNote,
                'decided_matter_ids' => $decidedMatterIds,
                'punishments' => $punishments,
                'special_outcome' => $specialOutcome,
                'allocations' => $allocations,
            ];
        }

        $fallbackMatterIds = collect($result)
            ->map(fn ($row) => array_values(array_filter(array_map('intval', (array) ($row['decided_matter_ids'] ?? [])))))
            ->first(fn ($ids) => ! empty($ids));

        if (! empty($fallbackMatterIds)) {
            foreach ($result as $idx => $row) {
                if (empty($row['decided_matter_ids'] ?? [])) {
                    $result[$idx]['decided_matter_ids'] = $fallbackMatterIds;
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalized
     * @param  array<int, mixed>  $existing
     * @return array<int, array<string, mixed>>
     */
    protected function restoreMissingDecidedMatterIdsFromExisting(array $normalized, array $existing): array
    {
        $existingByName = collect($existing)
            ->filter(fn ($row) => is_array($row) && trim((string) ($row['defendant_name'] ?? '')) !== '')
            ->keyBy(fn ($row) => trim((string) ($row['defendant_name'] ?? '')));

        foreach ($normalized as $index => $row) {
            $name = trim((string) ($row['defendant_name'] ?? ''));
            if ($name === '' || ! empty($row['decided_matter_ids'] ?? [])) {
                continue;
            }
            $existingRow = is_array($existing[$index] ?? null) ? $existing[$index] : null;
            if (! is_array($existingRow)) {
                $existingRow = $existingByName->get($name);
            }
            if (! is_array($existingRow)) {
                continue;
            }
            $existingIds = array_values(array_filter(array_map('intval', (array) ($existingRow['decided_matter_ids'] ?? []))));
            if ($existingIds !== []) {
                $normalized[$index]['decided_matter_ids'] = $existingIds;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $sentence
     */
    private function rawDefendantSentenceHasSentencingPayload(array $sentence): bool
    {
        $punishmentsRaw = $sentence['punishments'] ?? null;
        if (is_array($punishmentsRaw) && $punishmentsRaw !== []) {
            $damageAmount = $this->parseGroupedNumber($punishmentsRaw['damage_amount'] ?? '');
            $compensatedDamageAmount = $this->parseGroupedNumber($punishmentsRaw['compensated_damage_amount'] ?? '');
            $other = trim((string) ($punishmentsRaw['other'] ?? ''));
            $assetConfiscation = ! empty($punishmentsRaw['asset_confiscation'] ?? null);
            $destroyEvidence = ! empty($punishmentsRaw['destroy_evidence'] ?? null);
            if ($damageAmount > 0 || $compensatedDamageAmount > 0 || $other !== '' || $assetConfiscation || $destroyEvidence) {
                return true;
            }
            foreach ($punishmentsRaw as $block) {
                if (is_array($block) && ! empty($block['enabled'])) {
                    return true;
                }
            }
        }

        $allocationsRaw = $sentence['allocations'] ?? null;
        if (! is_array($allocationsRaw) || $allocationsRaw === []) {
            return false;
        }

        foreach ($allocationsRaw as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (! empty($row['matter_category_id']) && (int) $row['matter_category_id'] > 0) {
                return true;
            }
            if (! empty($row['punishments']) && is_array($row['punishments'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $sentence
     */
    private function normalizeOutcomeTrack(?string $raw, array $sentence): string
    {
        $t = is_string($raw) ? trim($raw) : '';
        if (in_array($t, [self::OUTCOME_TRACK_SENTENCE, self::OUTCOME_TRACK_NO_SENTENCE, self::OUTCOME_TRACK_TERMINATION], true)) {
            return $t;
        }

        $special = trim((string) ($sentence['special_outcome'] ?? ''));
        if ($special !== '' && in_array($special, static::specialOutcomeOptionValues(), true)) {
            return self::OUTCOME_TRACK_NO_SENTENCE;
        }

        $tk = trim((string) ($sentence['termination_kind'] ?? ''));
        $tn = trim((string) ($sentence['termination_note'] ?? ''));
        if (in_array($tk, [self::TERMINATION_DISMISS, self::TERMINATION_ACQUIT], true) || $tn !== '') {
            return self::OUTCOME_TRACK_TERMINATION;
        }

        if (! empty($sentence['allocations']) || ! empty($sentence['punishments']) || ! empty($sentence['decided_matter_ids'])) {
            return self::OUTCOME_TRACK_SENTENCE;
        }

        return self::OUTCOME_TRACK_SENTENCE;
    }

    /**
     * @return list<string>
     */
    protected static function specialOutcomeOptionValues(): array
    {
        return [
            'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
            'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
            'Хорих ял оногдуулахгүйгээр тэнссэн',
            'Эрүүгийн хариуцлагаас чөлөөлсөн',
        ];
    }

    private function parseGroupedNumber(mixed $value): int
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' || $digits === null ? 0 : (int) $digits;
    }

    /**
     * @param  array<int, mixed>  $allocationsRaw
     * @param  array<int, int>  $decidedMatterIds
     * @param  array<string, mixed>  $fallbackPunishments
     * @return array<int, array{matter_category_id:int,punishments:array<string,mixed>}>
     */
    private function normalizeSentenceAllocations(array $allocationsRaw, array $decidedMatterIds, array $fallbackPunishments): array
    {
        $result = [];

        foreach ($allocationsRaw as $allocation) {
            if (! is_array($allocation)) {
                continue;
            }
            $matterCategoryId = (int) ($allocation['matter_category_id'] ?? 0);
            if ($matterCategoryId < 1 || ! in_array($matterCategoryId, $decidedMatterIds, true)) {
                continue;
            }
            $punishmentsRaw = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
            $punishments = $this->normalizePunishments($punishmentsRaw, false);

            if (empty($punishments)) {
                continue;
            }

            $result[] = [
                'matter_category_id' => $matterCategoryId,
                'punishments' => $punishments,
            ];
        }

        if (! empty($result)) {
            $byMatter = [];
            foreach ($result as $row) {
                $byMatter[$row['matter_category_id']] = $row;
            }

            return array_values($byMatter);
        }

        if (count($decidedMatterIds) === 1 && ! empty($fallbackPunishments)) {
            return [[
                'matter_category_id' => $decidedMatterIds[0],
                'punishments' => $fallbackPunishments,
            ]];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $punishmentsRaw
     * @return array<string, mixed>
     */
    private function normalizePunishments(array $punishmentsRaw, bool $requireEnabledFlag = true): array
    {
        $punishments = [];

        $damageAmount = $this->parseGroupedNumber($punishmentsRaw['damage_amount'] ?? '');
        if ($damageAmount > 0) {
            $punishments['damage_amount'] = $damageAmount;
        }

        $compensatedDamageAmount = $this->parseGroupedNumber($punishmentsRaw['compensated_damage_amount'] ?? '');
        if ($compensatedDamageAmount > 0) {
            $punishments['compensated_damage_amount'] = $compensatedDamageAmount;
        }

        if (! empty($punishmentsRaw['asset_confiscation'] ?? null)) {
            $punishments['asset_confiscation'] = true;
        }

        if (! empty($punishmentsRaw['destroy_evidence'] ?? null)) {
            $punishments['destroy_evidence'] = true;
        }

        $other = trim((string) ($punishmentsRaw['other'] ?? ''));
        if ($other !== '') {
            $punishments['other'] = $other;
        }

        if ((! $requireEnabledFlag || ! empty($punishmentsRaw['fine']['enabled'] ?? null)) && is_array($punishmentsRaw['fine'] ?? null)) {
            $punishments['fine'] = [
                'fine_units' => $this->parseGroupedNumber($punishmentsRaw['fine']['fine_units'] ?? ''),
            ];
        }
        if ((! $requireEnabledFlag || ! empty($punishmentsRaw['community_service']['enabled'] ?? null)) && is_array($punishmentsRaw['community_service'] ?? null)) {
            $hoursRaw = preg_replace('/\D+/', '', (string) ($punishmentsRaw['community_service']['hours'] ?? '0'));
            $hours = (int) ($hoursRaw === '' ? '0' : $hoursRaw);
            if (strlen((string) $hours) > 3) {
                throw ValidationException::withMessages([
                    'notes_defendant_sentences' => 'Нийтэд тустай ажил 3 оронтой тоо байна.',
                ]);
            }
            if ($hours > 720) {
                throw ValidationException::withMessages([
                    'notes_defendant_sentences' => 'Нийтэд тустай ажил 720 цагаас ихгүй байна.',
                ]);
            }
            $punishments['community_service'] = ['hours' => max(0, $hours)];
        }

        foreach (['travel_restriction', 'imprisonment_open', 'imprisonment_closed', 'rights_ban_public_service', 'rights_ban_professional_activity', 'rights_ban_driving'] as $key) {
            if ((! $requireEnabledFlag || ! empty($punishmentsRaw[$key]['enabled'] ?? null)) && is_array($punishmentsRaw[$key] ?? null)) {
                $years = max(0, (int) ($punishmentsRaw[$key]['years'] ?? 0));
                $months = (int) ($punishmentsRaw[$key]['months'] ?? 0);
                if ($months < 0 || $months > 12) {
                    throw ValidationException::withMessages([
                        'notes_defendant_sentences' => 'Сар 0-12 хооронд байна.',
                    ]);
                }
                $punishments[$key] = ['years' => $years, 'months' => $months];
            }
        }

        return $punishments;
    }

    /**
     * @param  array<string, mixed>  $sentence
     */
    protected function assertNotesDefendantSentenceValidWhenIssued(int $index, array $sentence, bool $mustValidateSentences): void
    {
        if (! $mustValidateSentences) {
            return;
        }

        $track = $sentence['outcome_track'] ?? self::OUTCOME_TRACK_SENTENCE;

        if ($track === self::OUTCOME_TRACK_SENTENCE) {
            $hasAlloc = ! empty($sentence['allocations']);
            $hasPun = ! empty($sentence['punishments']);
            if (! $hasAlloc && ! $hasPun) {
                throw ValidationException::withMessages([
                    "notes_defendant_sentences.{$index}.allocations" => 'Ял оноох сонгосон бол зүйл анги болон ялын мэдээллийг бөглөнө үү.',
                ]);
            }

            return;
        }

        if ($track === self::OUTCOME_TRACK_NO_SENTENCE) {
            if (empty($sentence['special_outcome'])) {
                throw ValidationException::withMessages([
                    "notes_defendant_sentences.{$index}.special_outcome" => 'Ял оноохгүй сонгосон бол тусгай шийдвэрийн нэгийг сонгоно уу.',
                ]);
            }

            return;
        }

        if ($track === self::OUTCOME_TRACK_TERMINATION) {
            if (empty($sentence['termination_kind']) || ! in_array($sentence['termination_kind'], [self::TERMINATION_DISMISS, self::TERMINATION_ACQUIT], true)) {
                throw ValidationException::withMessages([
                    "notes_defendant_sentences.{$index}.termination_kind" => 'Хэрэгсэхгүй болгох эсвэл цагаатгахыг сонгоно уу.',
                ]);
            }
            if (trim((string) ($sentence['termination_note'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "notes_defendant_sentences.{$index}.termination_note" => 'Утга / тайлбарыг оруулна уу.',
                ]);
            }
        }
    }
}
