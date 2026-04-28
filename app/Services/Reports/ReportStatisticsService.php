<?php

namespace App\Services\Reports;

use App\Models\MatterCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ReportStatisticsService
{
    private const PUNISHMENT_LABELS = [
        'fine' => 'Торгох',
        'community_service' => 'Нийтэд тустай ажил',
        'travel_restriction' => 'Зорчих эрхийг хязгаарлах',
        'imprisonment_open' => 'Хорих (Нээлттэй)',
        'imprisonment_closed' => 'Хорих (Хаалттай)',
        'rights_ban_public_service' => 'Эрх хасах (Нийтийн алба)',
        'rights_ban_professional_activity' => 'Эрх хасах (Мэргэжлийн үйл ажиллагаа)',
        'rights_ban_driving' => 'Эрх хасах (Жолоодох эрх)',
    ];

    private const SPECIAL_OUTCOME_LABELS = [
        'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
        'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн',
        'Хорих ял оногдуулахгүйгээр тэнссэн',
        'Эрүүгийн хариуцлагаас чөлөөлсөн',
    ];

    private const AGE_BUCKET_LABELS = [
        '14_15' => '14-15',
        '16_17' => '16-17',
        '18_21' => '18-21',
        '22_29' => '22-29',
        '30_34' => '30-34',
        '35_plus' => '35-аас дээш',
    ];

    /**
     * @return array<string,mixed>
     */
    public function buildSentencingStats(Builder $base): array
    {
        $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
        $punishmentCounts = [];
        $articleMetrics = [];
        $crossCounts = [];
        $specialOutcomeCounts = [];
        $ageGenderCounts = [];
        foreach (self::AGE_BUCKET_LABELS as $bucketKey => $_) {
            $ageGenderCounts[$bucketKey] = ['female' => 0, 'male' => 0, 'female_55_plus' => 0, 'male_60_plus' => 0];
        }

        $hearings = $base->get(['notes_defendant_sentences', 'hearing_date']);
        $convictedTotal = 0;
        $convictedPeopleTotal = 0;
        $convictedLegalEntityTotal = 0;
        $imprisonmentPeopleTotal = 0;
        $imprisonmentFemale = 0;
        $imprisonmentMinor = 0;
        $imprisonmentExecuteTotal = 0;
        $finePeopleTotal = 0;
        $fineLegalEntityTotal = 0;
        $fineAmountTotal = 0;
        $communityServicePeopleTotal = 0;
        $travelRestrictionPeopleTotal = 0;
        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : [];
            foreach ($sentences as $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }
                $registry = (string) ($sentence['defendant_registry'] ?? '');
                $demographics = $this->parseDemographicsFromRegistry($registry, $hearing->hearing_date);
                $isPerson = $demographics !== null;
                $specialOutcome = trim((string) ($sentence['special_outcome'] ?? ''));
                $track = trim((string) ($sentence['outcome_track'] ?? ''));
                if (! in_array($track, ['sentence', 'no_sentence', 'termination'], true)) {
                    if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                        $track = 'no_sentence';
                    } elseif (trim((string) ($sentence['termination_kind'] ?? '')) !== '' || trim((string) ($sentence['termination_note'] ?? '')) !== '') {
                        $track = 'termination';
                    } else {
                        $track = 'sentence';
                    }
                }
                $terminationKind = trim((string) ($sentence['termination_kind'] ?? ''));
                $sentenceMatterIds = collect($sentence['decided_matter_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                if (empty($sentenceMatterIds)) {
                    $sentenceMatterIds = collect($sentence['allocations'] ?? [])
                        ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                        ->filter(fn ($id) => $id > 0)
                        ->unique()
                        ->values()
                        ->all();
                }
                $sentenceMatterNames = collect($sentenceMatterIds)
                    ->map(fn ($id) => $matterMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();
                foreach ($sentenceMatterNames as $articleName) {
                    $articleMetrics[$articleName] ??= [
                        'count' => 0,
                        'sentence_count' => 0,
                        'no_sentence_count' => 0,
                        'termination_count' => 0,
                        'dismiss_count' => 0,
                        'acquit_count' => 0,
                        'special_outcome_count' => 0,
                        'community_hours_total' => 0,
                        'fine_units_total' => 0,
                        'damage_amount_total' => 0,
                    ];
                    $articleMetrics[$articleName]['count']++;
                    if ($track === 'sentence') {
                        $articleMetrics[$articleName]['sentence_count']++;
                    } elseif ($track === 'no_sentence') {
                        $articleMetrics[$articleName]['no_sentence_count']++;
                    } elseif ($track === 'termination') {
                        $articleMetrics[$articleName]['termination_count']++;
                        if ($terminationKind === 'dismiss') {
                            $articleMetrics[$articleName]['dismiss_count']++;
                        } elseif ($terminationKind === 'acquit') {
                            $articleMetrics[$articleName]['acquit_count']++;
                        }
                    }
                    if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                        $articleMetrics[$articleName]['special_outcome_count']++;
                    }
                }
                if ($specialOutcome !== '' && in_array($specialOutcome, self::SPECIAL_OUTCOME_LABELS, true)) {
                    $specialOutcomeCounts[$specialOutcome] = (int) ($specialOutcomeCounts[$specialOutcome] ?? 0) + 1;

                    continue;
                }
                $hasSentencePunishment = ! empty($sentence['punishments']) || ! empty($sentence['allocations']);
                if ($hasSentencePunishment) {
                    $convictedTotal++;
                    if ($isPerson) {
                        $convictedPeopleTotal++;
                        $bucket = $demographics['age_bucket'];
                        $gender = $demographics['gender'];
                        $ageGenderCounts[$bucket][$gender] = (int) ($ageGenderCounts[$bucket][$gender] ?? 0) + 1;
                        $age = $demographics['age'];
                        if ($gender === 'female' && $age >= 55) {
                            $ageGenderCounts['35_plus']['female_55_plus'] = (int) ($ageGenderCounts['35_plus']['female_55_plus'] ?? 0) + 1;
                        }
                        if ($gender === 'male' && $age >= 60) {
                            $ageGenderCounts['35_plus']['male_60_plus'] = (int) ($ageGenderCounts['35_plus']['male_60_plus'] ?? 0) + 1;
                        }
                    } else {
                        $convictedLegalEntityTotal++;
                    }
                }
                $allocations = is_array($sentence['allocations'] ?? null) ? $sentence['allocations'] : [];
                $hasImprisonmentInSentence = false;
                $hasFineInSentence = false;
                $hasCommunityServiceInSentence = false;
                $hasTravelRestrictionInSentence = false;
                $sentenceFineDamageAmount = 0;
                if (! empty($allocations)) {
                    foreach ($allocations as $allocation) {
                        if (! is_array($allocation)) {
                            continue;
                        }
                        $punishments = is_array($allocation['punishments'] ?? null) ? $allocation['punishments'] : [];
                        $articleName = $matterMap[(int) ($allocation['matter_category_id'] ?? 0)] ?? null;
                        if ($articleName === null) {
                            continue;
                        }
                        $articleMetrics[$articleName] ??= [
                            'count' => 0,
                            'sentence_count' => 0,
                            'no_sentence_count' => 0,
                            'termination_count' => 0,
                            'dismiss_count' => 0,
                            'acquit_count' => 0,
                            'special_outcome_count' => 0,
                            'community_hours_total' => 0,
                            'fine_units_total' => 0,
                            'damage_amount_total' => 0,
                        ];
                        $articleMetrics[$articleName]['damage_amount_total'] += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        $articleMetrics[$articleName]['fine_units_total'] += (int) (($punishments['fine']['fine_units'] ?? 0));
                        $articleMetrics[$articleName]['community_hours_total'] += (int) (($punishments['community_service']['hours'] ?? 0));
                        foreach (array_keys($punishments) as $punishmentKey) {
                            $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                            $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                            if ($punishmentKey === 'imprisonment_open' || $punishmentKey === 'imprisonment_closed') {
                                $hasImprisonmentInSentence = true;
                            }
                            if ($punishmentKey === 'fine') {
                                $hasFineInSentence = true;
                                $sentenceFineDamageAmount += (int) (($punishments['fine']['damage_amount'] ?? 0));
                            }
                            if ($punishmentKey === 'community_service') {
                                $hasCommunityServiceInSentence = true;
                            }
                            if ($punishmentKey === 'travel_restriction') {
                                $hasTravelRestrictionInSentence = true;
                            }
                            $crossKey = $label.'|'.$articleName;
                            $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                        }
                    }
                } else {
                    $punishments = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
                    $articleNames = collect($sentence['decided_matter_ids'] ?? [])
                        ->map(fn ($id) => $matterMap[(int) $id] ?? null)
                        ->filter()
                        ->values()
                        ->all();
                    foreach ($articleNames as $articleName) {
                        $articleMetrics[$articleName] ??= [
                            'count' => 0,
                            'sentence_count' => 0,
                            'no_sentence_count' => 0,
                            'termination_count' => 0,
                            'dismiss_count' => 0,
                            'acquit_count' => 0,
                            'special_outcome_count' => 0,
                            'community_hours_total' => 0,
                            'fine_units_total' => 0,
                            'damage_amount_total' => 0,
                        ];
                        $articleMetrics[$articleName]['damage_amount_total'] += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        $articleMetrics[$articleName]['fine_units_total'] += (int) (($punishments['fine']['fine_units'] ?? 0));
                        $articleMetrics[$articleName]['community_hours_total'] += (int) (($punishments['community_service']['hours'] ?? 0));
                    }

                    foreach (array_keys($punishments) as $punishmentKey) {
                        $label = self::PUNISHMENT_LABELS[$punishmentKey] ?? $punishmentKey;
                        $punishmentCounts[$label] = (int) ($punishmentCounts[$label] ?? 0) + 1;
                        if ($punishmentKey === 'imprisonment_open' || $punishmentKey === 'imprisonment_closed') {
                            $hasImprisonmentInSentence = true;
                        }
                        if ($punishmentKey === 'fine') {
                            $hasFineInSentence = true;
                            $sentenceFineDamageAmount += (int) (($punishments['fine']['damage_amount'] ?? 0));
                        }
                        if ($punishmentKey === 'community_service') {
                            $hasCommunityServiceInSentence = true;
                        }
                        if ($punishmentKey === 'travel_restriction') {
                            $hasTravelRestrictionInSentence = true;
                        }
                        if (empty($articleNames)) {
                            $crossKey = $label.'|—';
                            $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                        } else {
                            foreach ($articleNames as $articleName) {
                                $crossKey = $label.'|'.$articleName;
                                $crossCounts[$crossKey] = (int) ($crossCounts[$crossKey] ?? 0) + 1;
                            }
                        }
                    }
                }

                if ($hasImprisonmentInSentence) {
                    $imprisonmentExecuteTotal++;
                    if ($isPerson) {
                        $imprisonmentPeopleTotal++;
                        if (($demographics['gender'] ?? null) === 'female') {
                            $imprisonmentFemale++;
                        }
                        if (($demographics['age'] ?? 0) < 18) {
                            $imprisonmentMinor++;
                        }
                    }
                }
                if ($hasFineInSentence) {
                    if ($isPerson) {
                        $finePeopleTotal++;
                    } else {
                        $fineLegalEntityTotal++;
                    }
                    $fineAmountTotal += $sentenceFineDamageAmount;
                }
                if ($hasCommunityServiceInSentence && $isPerson) {
                    $communityServicePeopleTotal++;
                }
                if ($hasTravelRestrictionInSentence && $isPerson) {
                    $travelRestrictionPeopleTotal++;
                }
            }
        }

        ksort($punishmentCounts);
        ksort($articleMetrics);
        ksort($crossCounts);
        ksort($specialOutcomeCounts);

        $form75Rows = [
            ['label' => '52. Ял шийтгүүлсэн-бүгд', 'value' => $convictedTotal],
            ['label' => '53. Ял шийтгүүлсэн хүн-бүгд', 'value' => $convictedPeopleTotal],
            ['label' => '54. Нас: 14-15', 'value' => (int) (($ageGenderCounts['14_15']['female'] ?? 0) + ($ageGenderCounts['14_15']['male'] ?? 0))],
            ['label' => '55. Нас: 16-17', 'value' => (int) (($ageGenderCounts['16_17']['female'] ?? 0) + ($ageGenderCounts['16_17']['male'] ?? 0))],
            ['label' => '56. Нас: 18-21', 'value' => (int) (($ageGenderCounts['18_21']['female'] ?? 0) + ($ageGenderCounts['18_21']['male'] ?? 0))],
            ['label' => '57. Нас: 22-29', 'value' => (int) (($ageGenderCounts['22_29']['female'] ?? 0) + ($ageGenderCounts['22_29']['male'] ?? 0))],
            ['label' => '58. Нас: 30-34', 'value' => (int) (($ageGenderCounts['30_34']['female'] ?? 0) + ($ageGenderCounts['30_34']['male'] ?? 0))],
            ['label' => '59. Нас: 35-аас дээш', 'value' => (int) (($ageGenderCounts['35_plus']['female'] ?? 0) + ($ageGenderCounts['35_plus']['male'] ?? 0))],
            ['label' => '60. Үүнээс 55+ насны эмэгтэй', 'value' => (int) ($ageGenderCounts['35_plus']['female_55_plus'] ?? 0)],
            ['label' => '61. Үүнээс 60+ насны эрэгтэй', 'value' => (int) ($ageGenderCounts['35_plus']['male_60_plus'] ?? 0)],
            ['label' => '62. Ял шийтгүүлсэн хуулийн этгээд-бүгд', 'value' => $convictedLegalEntityTotal],
            ['label' => '63. Хорих ял шийтгүүлсэн хүн-бүгд', 'value' => $imprisonmentPeopleTotal],
            ['label' => '64. Үүнээс эмэгтэй', 'value' => $imprisonmentFemale],
            ['label' => '65. Үүнээс өсвөр насны хүн', 'value' => $imprisonmentMinor],
            ['label' => '66. Хорих ял биелэн эдлүүлэх-бүгд', 'value' => $imprisonmentExecuteTotal],
            ['label' => '67. Торгох ял шийтгүүлсэн (хүн)', 'value' => $finePeopleTotal],
            ['label' => '68. Торгох ял шийтгүүлсэн (хуулийн этгээд)', 'value' => $fineLegalEntityTotal],
            ['label' => '69. Торгуулийн нийт дүн /төг/', 'value' => $fineAmountTotal],
            ['label' => '70. Нийтэд тустай ажил хийх ял', 'value' => $communityServicePeopleTotal],
            ['label' => '71. Зорчих эрхийг хязгаарлах ял', 'value' => $travelRestrictionPeopleTotal],
            ['label' => '72. Хүмүүжлийн чанартай албадлагын арга хэмжээ', 'value' => (int) ($specialOutcomeCounts['Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн'] ?? 0)],
            ['label' => '73. Эмнэлгийн чанартай албадлагын арга хэмжээ', 'value' => (int) ($specialOutcomeCounts['Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн'] ?? 0)],
            ['label' => '74. Хорих ял оногдуулахгүйгээр тэнссэн', 'value' => (int) ($specialOutcomeCounts['Хорих ял оногдуулахгүйгээр тэнссэн'] ?? 0)],
            ['label' => '75. Эрүүгийн хариуцлагаас чөлөөлсөн', 'value' => (int) ($specialOutcomeCounts['Эрүүгийн хариуцлагаас чөлөөлсөн'] ?? 0)],
        ];

        return [
            'punishmentRows' => collect($punishmentCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'articleRows' => collect($articleMetrics)->map(fn ($row, $name) => array_merge(['name' => $name], $row))->values()->all(),
            'crossRows' => collect($crossCounts)->map(function ($count, $key) {
                [$punishment, $article] = explode('|', (string) $key, 2);

                return ['punishment' => $punishment, 'article' => $article, 'count' => (int) $count];
            })->values()->all(),
            'specialOutcomeRows' => collect($specialOutcomeCounts)->map(fn ($count, $name) => ['name' => $name, 'count' => (int) $count])->values()->all(),
            'ageGenderRows' => collect(self::AGE_BUCKET_LABELS)->map(function ($label, $bucketKey) use ($ageGenderCounts) {
                $female = (int) ($ageGenderCounts[$bucketKey]['female'] ?? 0);
                $male = (int) ($ageGenderCounts[$bucketKey]['male'] ?? 0);

                return [
                    'age_group' => $label,
                    'female' => $female,
                    'male' => $male,
                    'total' => $female + $male,
                ];
            })->values()->all(),
            'ageGenderHighlights' => [
                'female_55_plus' => (int) (($ageGenderCounts['35_plus']['female_55_plus'] ?? 0)),
                'male_60_plus' => (int) (($ageGenderCounts['35_plus']['male_60_plus'] ?? 0)),
            ],
            'form75Rows' => $form75Rows,
        ];
    }

    /**
     * @return array{gender: 'female'|'male', age_bucket: string, age: int}|null
     */
    private function parseDemographicsFromRegistry(string $registry, mixed $referenceDate): ?array
    {
        $normalized = mb_strtoupper(trim($registry), 'UTF-8');
        $digits = preg_replace('/\D+/', '', $normalized);
        if (! is_string($digits) || strlen($digits) < 8) {
            return null;
        }

        $datePart = substr($digits, 0, 6);
        $yy = (int) substr($datePart, 0, 2);
        $mmRaw = (int) substr($datePart, 2, 2);
        $dd = (int) substr($datePart, 4, 2);
        $mm = $mmRaw;
        $fullYear = 1900 + $yy;
        if ($mmRaw > 20) {
            $mm = $mmRaw - 20;
            $fullYear = 2000 + $yy;
        }
        if (! checkdate($mm, $dd, $fullYear)) {
            return null;
        }

        $genderDigit = (int) substr($digits, 6, 1);
        $gender = $genderDigit % 2 === 0 ? 'female' : 'male';
        $birthDate = Carbon::create($fullYear, $mm, $dd)->startOfDay();
        $asOfDate = $referenceDate ? Carbon::parse($referenceDate)->startOfDay() : now()->startOfDay();
        $age = $birthDate->diffInYears($asOfDate);
        $bucket = match (true) {
            $age >= 14 && $age <= 15 => '14_15',
            $age >= 16 && $age <= 17 => '16_17',
            $age >= 18 && $age <= 21 => '18_21',
            $age >= 22 && $age <= 29 => '22_29',
            $age >= 30 && $age <= 34 => '30_34',
            $age >= 35 => '35_plus',
            default => null,
        };
        if ($bucket === null) {
            return null;
        }

        return [
            'gender' => $gender,
            'age_bucket' => $bucket,
            'age' => $age,
        ];
    }
}
