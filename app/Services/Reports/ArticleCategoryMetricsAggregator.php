<?php

namespace App\Services\Reports;

class ArticleCategoryMetricsAggregator
{
    /**
     * @return list<array{key: string, label: string, numeric: bool}>
     */
    public static function tableColumns(): array
    {
        return [
            ['key' => 'acquit_count', 'label' => 'Цагаатгах', 'numeric' => true],
            ['key' => 'dismiss_count', 'label' => 'Хэрэгсэхгүй болгох', 'numeric' => true],
            ['key' => 'release_from_criminal_liability_count', 'label' => 'Эрүүгийн хариуцлагаас чөлөөлсөн', 'numeric' => true],
            ['key' => 'medical_measure_count', 'label' => 'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн', 'numeric' => true],
            ['key' => 'probation_without_imprisonment_count', 'label' => 'Хорих ял оногдуулахгүйгээр тэнссэн', 'numeric' => true],
            ['key' => 'educational_measure_count', 'label' => 'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн', 'numeric' => true],
            ['key' => 'imprisonment_closed_years', 'label' => 'Хорих ял - Хаалттай (жил)', 'numeric' => true],
            ['key' => 'imprisonment_closed_months', 'label' => 'Хорих ял - Хаалттай (сар)', 'numeric' => true],
            ['key' => 'imprisonment_open_years', 'label' => 'Хорих ял - Нээлттэй (жил)', 'numeric' => true],
            ['key' => 'imprisonment_open_months', 'label' => 'Хорих ял - Нээлттэй (сар)', 'numeric' => true],
            ['key' => 'community_service_hours', 'label' => 'Нийтэд тустай ажил', 'numeric' => true],
            ['key' => 'travel_restriction_years', 'label' => 'Зорчих эрх (жил)', 'numeric' => true],
            ['key' => 'travel_restriction_months', 'label' => 'Зорчих эрх (сар)', 'numeric' => true],
            ['key' => 'fine_units', 'label' => 'Торгох (нэгж)', 'numeric' => true],
            ['key' => 'rights_ban_driving_years', 'label' => 'Жолоодох эрх хасах (жил)', 'numeric' => true],
            ['key' => 'rights_ban_driving_months', 'label' => 'Жолоодох эрх хасах (сар)', 'numeric' => true],
            ['key' => 'rights_ban_professional_activity_years', 'label' => 'Мэргэжлийн эрх хасах (жил)', 'numeric' => true],
            ['key' => 'rights_ban_professional_activity_months', 'label' => 'Мэргэжлийн эрх хасах (сар)', 'numeric' => true],
            ['key' => 'rights_ban_public_service_years', 'label' => 'Нийтийн албаны эрх хасах (жил)', 'numeric' => true],
            ['key' => 'rights_ban_public_service_months', 'label' => 'Нийтийн албаны эрх хасах (сар)', 'numeric' => true],
            ['key' => 'damage_amount', 'label' => 'Хохирлын дүн', 'numeric' => true],
            ['key' => 'compensated_damage_amount', 'label' => 'Шүүхийн шатанд нөхөн төлүүлсэн хохирлын хэмжээ', 'numeric' => true],
            ['key' => 'asset_confiscation_count', 'label' => 'Хөрөнгө орлого хураах', 'numeric' => true],
            ['key' => 'destroy_evidence_count', 'label' => 'Эд мөрийн баримт устгуулах', 'numeric' => true],
        ];
    }

    /**
     * @param  iterable<object|array<string, mixed>>  $hearings
     * @param  array<int, string>  $matterMap
     * @return list<array<string, mixed>>
     */
    public function buildRows(iterable $hearings, array $matterMap): array
    {
        $metrics = [];

        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences ?? null)
                ? $hearing->notes_defendant_sentences
                : [];

            foreach ($sentences as $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }

                $this->accumulateSentence($metrics, $sentence, $matterMap);
            }
        }

        ksort($metrics);

        return collect($metrics)
            ->map(fn (array $row, string $name) => array_merge(['name' => $name], $row))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array<string, int>>  $metrics
     * @param  array<string, mixed>  $sentence
     * @param  array<int, string>  $matterMap
     */
    private function accumulateSentence(array &$metrics, array $sentence, array $matterMap): void
    {
        $matterIds = $this->resolveDecidedMatterIds($sentence);
        if ($matterIds === []) {
            return;
        }

        $matterDecisions = collect($sentence['matter_decisions'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values()
            ->all();

        $specialOutcome = trim((string) ($sentence['special_outcome'] ?? ''));
        $outcomeTrack = trim((string) ($sentence['outcome_track'] ?? ''));
        if (! in_array($outcomeTrack, ['sentence', 'no_sentence', 'termination'], true)) {
            if ($specialOutcome !== '' && in_array($specialOutcome, $this->specialOutcomeKeys(), true)) {
                $outcomeTrack = 'no_sentence';
            } elseif (trim((string) ($sentence['termination_kind'] ?? '')) !== '' || trim((string) ($sentence['termination_note'] ?? '')) !== '') {
                $outcomeTrack = 'termination';
            } else {
                $outcomeTrack = 'sentence';
            }
        }

        $terminationKind = trim((string) ($sentence['termination_kind'] ?? ''));

        foreach ($matterIds as $matterId) {
            $articleName = $matterMap[$matterId] ?? null;
            if ($articleName === null || $articleName === '') {
                continue;
            }

            $metrics[$articleName] ??= $this->emptyRow();
            $row = &$metrics[$articleName];

            $matterDecisionType = $this->matterDecisionTypeForMatterId($matterDecisions, $matterId);
            $this->applyDecisionCounts(
                $row,
                $matterDecisionType,
                $specialOutcome,
                $outcomeTrack,
                $terminationKind,
                count($matterIds)
            );

            $punishments = $this->punishmentsForMatter($sentence, $matterId);
            $this->applyPunishmentTotals($row, $punishments);

            unset($row);
        }
    }

    /**
     * @return array<string, int>
     */
    private function emptyRow(): array
    {
        $row = [];
        foreach (self::tableColumns() as $column) {
            $row[$column['key']] = 0;
        }

        return $row;
    }

    /**
     * @param  array<string, int>  $row
     */
    private function applyDecisionCounts(
        array &$row,
        ?string $matterDecisionType,
        string $specialOutcome,
        string $outcomeTrack,
        string $terminationKind,
        int $matterCount
    ): void {
        $effectiveType = $matterDecisionType;
        if ($effectiveType === null && $matterCount === 1) {
            if ($outcomeTrack === 'termination') {
                $effectiveType = $terminationKind === 'acquit' ? 'acquit' : ($terminationKind === 'dismiss' ? 'dismiss' : null);
            } elseif ($outcomeTrack === 'no_sentence') {
                $effectiveType = 'no_sentence';
            } elseif ($outcomeTrack === 'sentence') {
                $effectiveType = 'sentence';
            }
        }

        if ($effectiveType === 'acquit') {
            $row['acquit_count']++;

            return;
        }

        if ($effectiveType === 'dismiss') {
            $row['dismiss_count']++;

            return;
        }

        if ($effectiveType === 'no_sentence' || ($effectiveType === null && $outcomeTrack === 'no_sentence')) {
            $this->incrementSpecialOutcome($row, $specialOutcome);

            return;
        }
    }

    /**
     * @param  array<string, int>  $row
     */
    private function incrementSpecialOutcome(array &$row, string $specialOutcome): void
    {
        match ($specialOutcome) {
            'Эрүүгийн хариуцлагаас чөлөөлсөн' => $row['release_from_criminal_liability_count']++,
            'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн' => $row['medical_measure_count']++,
            'Хорих ял оногдуулахгүйгээр тэнссэн' => $row['probation_without_imprisonment_count']++,
            'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн' => $row['educational_measure_count']++,
            default => null,
        };
    }

    /**
     * @param  array<string, int>  $row
     * @param  array<string, mixed>  $punishments
     */
    private function applyPunishmentTotals(array &$row, array $punishments): void
    {
        if ($punishments === []) {
            return;
        }

        $row['imprisonment_closed_years'] += max(0, (int) (($punishments['imprisonment_closed']['years'] ?? 0)));
        $row['imprisonment_closed_months'] += max(0, (int) (($punishments['imprisonment_closed']['months'] ?? 0)));
        $row['imprisonment_open_years'] += max(0, (int) (($punishments['imprisonment_open']['years'] ?? 0)));
        $row['imprisonment_open_months'] += max(0, (int) (($punishments['imprisonment_open']['months'] ?? 0)));
        $row['community_service_hours'] += max(0, (int) (($punishments['community_service']['hours'] ?? 0)));
        $row['travel_restriction_years'] += max(0, (int) (($punishments['travel_restriction']['years'] ?? 0)));
        $row['travel_restriction_months'] += max(0, (int) (($punishments['travel_restriction']['months'] ?? 0)));
        $row['fine_units'] += max(0, (int) (($punishments['fine']['fine_units'] ?? 0)));
        $row['rights_ban_driving_years'] += max(0, (int) (($punishments['rights_ban_driving']['years'] ?? 0)));
        $row['rights_ban_driving_months'] += max(0, (int) (($punishments['rights_ban_driving']['months'] ?? 0)));
        $row['rights_ban_professional_activity_years'] += max(0, (int) (($punishments['rights_ban_professional_activity']['years'] ?? 0)));
        $row['rights_ban_professional_activity_months'] += max(0, (int) (($punishments['rights_ban_professional_activity']['months'] ?? 0)));
        $row['rights_ban_public_service_years'] += max(0, (int) (($punishments['rights_ban_public_service']['years'] ?? 0)));
        $row['rights_ban_public_service_months'] += max(0, (int) (($punishments['rights_ban_public_service']['months'] ?? 0)));
        $row['damage_amount'] += max(0, (int) (($punishments['fine']['damage_amount'] ?? $punishments['damage_amount'] ?? 0)));
        $row['compensated_damage_amount'] += max(0, (int) ($punishments['compensated_damage_amount'] ?? 0));

        if (! empty($punishments['asset_confiscation'])) {
            $row['asset_confiscation_count']++;
        }

        if (! empty($punishments['destroy_evidence'])) {
            $row['destroy_evidence_count']++;
        }
    }

    /**
     * @param  array<string, mixed>  $sentence
     * @return list<int>
     */
    private function resolveDecidedMatterIds(array $sentence): array
    {
        $ids = collect($sentence['decided_matter_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        $ids = collect($sentence['allocations'] ?? [])
            ->map(fn ($row) => is_array($row) ? (int) ($row['matter_category_id'] ?? 0) : 0)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        return collect($sentence['matter_decisions'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $matterDecisions
     */
    private function matterDecisionTypeForMatterId(array $matterDecisions, int $matterId): ?string
    {
        foreach ($matterDecisions as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['matter_category_id'] ?? 0) !== $matterId) {
                continue;
            }
            $type = trim((string) ($row['decision_type'] ?? ''));
            if (in_array($type, ['sentence', 'no_sentence', 'dismiss', 'acquit'], true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $sentence
     * @return array<string, mixed>
     */
    private function punishmentsForMatter(array $sentence, int $matterId): array
    {
        $topLevel = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
        $allocations = is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [];

        foreach ($allocations as $allocation) {
            if (! is_array($allocation)) {
                continue;
            }
            if ((int) ($allocation['matter_category_id'] ?? 0) !== $matterId) {
                continue;
            }
            $fromAlloc = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
            if ($fromAlloc !== []) {
                return $fromAlloc;
            }
        }

        $decidedOnly = collect($sentence['decided_matter_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($decidedOnly) === 1 && (int) $decidedOnly[0] === $matterId) {
            return $topLevel;
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function specialOutcomeKeys(): array
    {
        return [
            'Эрүүгийн хариуцлагаас чөлөөлсөн',
            'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
            'Хорих ял оногдуулахгүйгээр тэнссэн',
            'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
        ];
    }
}
