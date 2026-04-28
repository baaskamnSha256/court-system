<?php

namespace App\Services\Reports;

use App\Models\MatterCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DefendantDetailReportService
{
    /**
     * @return list<array{key:string,label:string}>
     */
    public function exportColumns(): array
    {
        return [
            ['key' => 'case_no', 'label' => 'Хэргийн дугаар'],
            ['key' => 'hearing_state', 'label' => 'Хурлын төлөв'],
            ['key' => 'hearing_date', 'label' => 'Хурлын огноо'],
            ['key' => 'hearing_time', 'label' => 'Цаг минут'],
            ['key' => 'courtroom', 'label' => 'Танхим'],
            ['key' => 'judge_panel', 'label' => 'Шүүх бүрэлдэхүүн болон шүүгч'],
            ['key' => 'defendant_name', 'label' => 'Шүүгдэгч'],
            ['key' => 'victim_name', 'label' => 'Хохирогч'],
            ['key' => 'victim_legal_rep', 'label' => 'Хохирогчийн хууль ёсны төлөөлөгч'],
            ['key' => 'preventive_measure', 'label' => 'Таслан сэргийлэх арга хэмжээ'],
            ['key' => 'prosecutor_name', 'label' => 'Улсын яллагч'],
            ['key' => 'incoming_matter', 'label' => 'Зүйл анги'],
            ['key' => 'defender_summary', 'label' => 'Өмгөөлөгч'],
            ['key' => 'witness_names', 'label' => 'Гэрч'],
            ['key' => 'expert_names', 'label' => 'Шинжээч'],
            ['key' => 'civil_plaintiff', 'label' => 'Иргэний нэхэмжлэгч'],
            ['key' => 'civil_defendant', 'label' => 'Иргэний хариуцагч'],
            ['key' => 'notes_handover_text', 'label' => 'Шүүх хуралдааны тойм'],
            ['key' => 'notes_clerk_name', 'label' => 'ШХНБДарга'],
            ['key' => 'decision_status', 'label' => 'Шүүх хуралдааны шийдвэр'],
            ['key' => 'decided_matter', 'label' => 'Шийдвэрлэсэн зүйл анги'],
            ['key' => 'acquit', 'label' => 'Цагаатгах'],
            ['key' => 'dismiss', 'label' => 'Хэрэгсэхгүй болгох'],
            ['key' => 'release_from_criminal_liability', 'label' => 'Эрүүгийн хариуцлагаас чөлөөлсөн'],
            ['key' => 'medical_measure', 'label' => 'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн'],
            ['key' => 'probation_without_imprisonment', 'label' => 'Хорих ял оногдуулахгүйгээр тэнссэн'],
            ['key' => 'educational_measure', 'label' => 'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн'],
            ['key' => 'imprisonment_closed', 'label' => 'Хорих ял - Хаалттай (жил/сар)'],
            ['key' => 'imprisonment_open', 'label' => 'Хорих ял - Нээлттэй (жил/сар)'],
            ['key' => 'community_service', 'label' => 'Нийтэд тустай ажил'],
            ['key' => 'travel_restriction_years', 'label' => 'Зорчих эрх (жил)'],
            ['key' => 'travel_restriction_months', 'label' => 'Зорчих эрх (сар)'],
            ['key' => 'fine_units', 'label' => 'Торгох (нэгж)'],
            ['key' => 'rights_ban_driving_years', 'label' => 'Жолоодох эрх хасах (жил)'],
            ['key' => 'rights_ban_driving_months', 'label' => 'Жолоодох эрх хасах (сар)'],
            ['key' => 'rights_ban_professional_activity_years', 'label' => 'Мэргэжлийн эрх хасах (жил)'],
            ['key' => 'rights_ban_professional_activity_months', 'label' => 'Мэргэжлийн эрх хасах (сар)'],
            ['key' => 'rights_ban_public_service_years', 'label' => 'Нийтийн албаны эрх хасах (жил)'],
            ['key' => 'rights_ban_public_service_months', 'label' => 'Нийтийн албаны эрх хасах (сар)'],
            ['key' => 'damage_amount', 'label' => 'Хохирлын дүн'],
            ['key' => 'compensated_damage_amount', 'label' => 'Шүүхийн шатанд нөхөн төлүүлсэн хохирлын хэмжээ'],
            ['key' => 'asset_confiscation', 'label' => 'Хөрөнгө орлого хураах'],
            ['key' => 'destroy_evidence', 'label' => 'Эд мөрийн баримт устгуулах'],
            ['key' => 'other_punishment', 'label' => 'Бусад'],
            ['key' => 'defendant_age', 'label' => 'Шүүгдэгчийн нас'],
            ['key' => 'defendant_gender', 'label' => 'Шүүгдэгчийн хүйс'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function buildRows(Builder $base): array
    {
        $matterMap = MatterCategory::query()->pluck('name', 'id')->all();
        $clerkMap = User::query()->pluck('name', 'id')->all();
        $rows = [];
        $hearingColumns = [
            'id',
            'case_no',
            'hearing_state',
            'hearing_date',
            'start_at',
            'courtroom',
            'judge_names_text',
            'victim_name',
            'victim_legal_rep',
            'preventive_measure',
            'prosecutor_name_text',
            'prosecutor_name',
            'prosecutor_id',
            'prosecutor_ids',
            'matter_category',
            'matter_category_ids',
            'defendant_lawyers_text',
            'victim_lawyers_text',
            'witnesses',
            'experts',
            'civil_plaintiff',
            'civil_defendant',
            'civil_plaintiff_lawyers',
            'civil_defendant_lawyers',
            'notes_handover_text',
            'clerk_id',
            'notes_decision_status',
            'defendant_names',
            'defendant_registries',
            'defendants',
            'notes_decided_matter',
            'notes_fine_units',
            'notes_damage_amount',
            'notes_defendant_sentences',
        ];
        $existingColumns = collect($hearingColumns)
            ->filter(fn (string $column) => Schema::hasColumn('hearings', $column))
            ->values()
            ->all();
        $hearings = $base
            ->whereNotNull('notes_decision_status')
            ->whereRaw('TRIM(notes_decision_status) = ?', ['Шийдвэрлэсэн'])
            ->orderBy('hearing_date')
            ->orderBy('id')
            ->get($existingColumns);
        $hearingIds = $hearings->pluck('id')->map(fn ($id) => (int) $id)->all();
        $judgePanelByHearingId = $this->loadJudgePanelByHearingId($hearingIds);

        foreach ($hearings as $hearing) {
            $sentences = is_array($hearing->notes_defendant_sentences) ? $hearing->notes_defendant_sentences : [];
            $fallbackDefendantNames = $this->extractFallbackDefendantNames($hearing);
            $fallbackDefendantRegistries = $this->extractFallbackDefendantRegistries($hearing);
            if ($sentences === [] && $fallbackDefendantNames !== []) {
                $sentences = collect($fallbackDefendantNames)
                    ->values()
                    ->map(fn (string $name, int $index) => [
                        'defendant_name' => $name,
                        'defendant_registry' => $fallbackDefendantRegistries[$index] ?? '',
                        'decided_matter_ids' => [],
                        'punishments' => [],
                        'special_outcome' => '',
                        'termination_kind' => '',
                        'termination_note' => '',
                        'outcome_track' => '',
                    ])
                    ->all();
            }

            foreach ($sentences as $sentenceIndex => $sentence) {
                if (! is_array($sentence)) {
                    continue;
                }
                $decidedMatterIds = collect($sentence['decided_matter_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $decidedMatterNames = collect($decidedMatterIds)
                    ->map(fn ($id) => $matterMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();
                $allMatterIds = collect($hearing->matter_category_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();
                $allMatterNames = collect($allMatterIds)
                    ->map(fn ($id) => $matterMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $punishments = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
                $specialOutcome = (string) ($sentence['special_outcome'] ?? '');
                $terminationKind = (string) ($sentence['termination_kind'] ?? '');
                $terminationNote = trim((string) ($sentence['termination_note'] ?? ''));
                $outcomeTrack = (string) ($sentence['outcome_track'] ?? '');
                $demographics = $this->parseDemographicsFromRegistry(
                    (string) ($sentence['defendant_registry'] ?? ''),
                    $hearing->hearing_date
                );
                $lawyers = $this->formatLawyerSummary($hearing);
                $judgePanel = $judgePanelByHearingId[(int) $hearing->id]
                    ?? $this->formatJudgePanel((string) ($hearing->judge_names_text ?? ''));
                $prosecutorName = $this->resolveProsecutorName($hearing);

                $rows[] = [
                    'case_no' => (string) ($hearing->case_no ?? ''),
                    'hearing_state' => (string) ($hearing->hearing_state ?? ''),
                    'hearing_date' => (string) ($hearing->hearing_date ?? ''),
                    'hearing_time' => $hearing->start_at ? (string) Carbon::parse($hearing->start_at)->format('H:i') : '',
                    'courtroom' => (string) ($hearing->courtroom ?? ''),
                    'judge_panel' => $judgePanel,
                    'defendant_name' => trim((string) ($sentence['defendant_name'] ?? '')) !== ''
                        ? (string) $sentence['defendant_name']
                        : (string) ($fallbackDefendantNames[$sentenceIndex] ?? ''),
                    'victim_name' => (string) ($hearing->victim_name ?? ''),
                    'victim_legal_rep' => (string) ($hearing->victim_legal_rep ?? ''),
                    'preventive_measure' => is_array($hearing->preventive_measure ?? null)
                        ? implode(', ', array_filter(array_map(fn ($value) => trim((string) $value), $hearing->preventive_measure)))
                        : (string) ($hearing->preventive_measure ?? ''),
                    'prosecutor_name' => $prosecutorName,
                    'incoming_matter' => $allMatterNames !== []
                        ? implode(', ', $allMatterNames)
                        : (string) ($hearing->matter_category ?? ''),
                    'defender_summary' => $lawyers,
                    'witness_names' => (string) ($hearing->witnesses ?? ''),
                    'expert_names' => (string) ($hearing->experts ?? ''),
                    'civil_plaintiff' => (string) ($hearing->civil_plaintiff ?? ''),
                    'civil_defendant' => (string) ($hearing->civil_defendant ?? ''),
                    'notes_handover_text' => (string) ($hearing->notes_handover_text ?? ''),
                    'notes_clerk_name' => (string) ($clerkMap[(int) ($hearing->clerk_id ?? 0)] ?? ''),
                    'decision_status' => $this->resolveSentenceDecisionStatus($outcomeTrack, $terminationKind, $specialOutcome, (string) ($hearing->notes_decision_status ?? '')),
                    'decided_matter' => implode(', ', $decidedMatterNames),
                    'acquit' => $terminationKind === 'acquit' ? ($terminationNote !== '' ? $terminationNote : 'Тийм') : '',
                    'dismiss' => $terminationKind === 'dismiss' ? ($terminationNote !== '' ? $terminationNote : 'Тийм') : '',
                    'release_from_criminal_liability' => $specialOutcome === 'Эрүүгийн хариуцлагаас чөлөөлсөн' ? 'Тийм' : '',
                    'medical_measure' => $specialOutcome === 'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн' ? 'Тийм' : '',
                    'probation_without_imprisonment' => $specialOutcome === 'Хорих ял оногдуулахгүйгээр тэнссэн' ? 'Тийм' : '',
                    'educational_measure' => $specialOutcome === 'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн' ? 'Тийм' : '',
                    'imprisonment_closed' => $this->formatYearsMonths($punishments['imprisonment_closed'] ?? null),
                    'imprisonment_open' => $this->formatYearsMonths($punishments['imprisonment_open'] ?? null),
                    'community_service' => $this->formatInteger($punishments['community_service']['hours'] ?? null),
                    'travel_restriction_years' => $this->formatDurationPart($punishments['travel_restriction'] ?? null, 'years'),
                    'travel_restriction_months' => $this->formatDurationPart($punishments['travel_restriction'] ?? null, 'months'),
                    'fine_units' => $this->formatInteger($punishments['fine']['fine_units'] ?? $hearing->notes_fine_units ?? null),
                    'rights_ban_driving_years' => $this->formatDurationPart($punishments['rights_ban_driving'] ?? null, 'years'),
                    'rights_ban_driving_months' => $this->formatDurationPart($punishments['rights_ban_driving'] ?? null, 'months'),
                    'rights_ban_professional_activity_years' => $this->formatDurationPart($punishments['rights_ban_professional_activity'] ?? null, 'years'),
                    'rights_ban_professional_activity_months' => $this->formatDurationPart($punishments['rights_ban_professional_activity'] ?? null, 'months'),
                    'rights_ban_public_service_years' => $this->formatDurationPart($punishments['rights_ban_public_service'] ?? null, 'years'),
                    'rights_ban_public_service_months' => $this->formatDurationPart($punishments['rights_ban_public_service'] ?? null, 'months'),
                    'damage_amount' => $this->formatInteger($punishments['damage_amount'] ?? $hearing->notes_damage_amount ?? null),
                    'compensated_damage_amount' => $this->formatInteger($punishments['compensated_damage_amount'] ?? null),
                    'asset_confiscation' => ! empty($punishments['asset_confiscation']) ? 'Тийм' : '',
                    'destroy_evidence' => ! empty($punishments['destroy_evidence']) ? 'Тийм' : '',
                    'other_punishment' => (string) ($punishments['other'] ?? ''),
                    'defendant_age' => $demographics['age'] ?? '',
                    'defendant_gender' => $demographics['gender'] ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array{gender: string, age: int}|null
     */
    private function parseDemographicsFromRegistry(string $registry, mixed $referenceDate): ?array
    {
        $registry = mb_strtoupper(trim($registry), 'UTF-8');
        if (! preg_match('/^[А-ЯӨҮЁ]{2}\d{8}$/u', $registry)) {
            return null;
        }

        $yy = (int) substr($registry, 2, 2);
        $mm = (int) substr($registry, 4, 2);
        $dd = (int) substr($registry, 6, 2);
        if ($mm < 1 || $mm > 12 || $dd < 1 || $dd > 31) {
            return null;
        }

        $reference = $referenceDate ? Carbon::parse($referenceDate) : now();
        $century = $yy <= ((int) $reference->format('y')) ? 2000 : 1900;
        $birthDate = Carbon::create($century + $yy, $mm, $dd, 0, 0, 0, $reference->timezone);
        $age = $birthDate->diffInYears($reference);

        $lastDigit = (int) substr($registry, -1);
        $gender = $lastDigit % 2 === 0 ? 'Эмэгтэй' : 'Эрэгтэй';

        return [
            'gender' => $gender,
            'age' => $age,
        ];
    }

    private function formatYearsMonths(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        $years = max(0, (int) ($value['years'] ?? 0));
        $months = max(0, (int) ($value['months'] ?? 0));

        return $years === 0 && $months === 0 ? '' : "{$years}/{$months}";
    }

    private function formatInteger(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '';
        }

        $intValue = (int) $value;

        return $intValue > 0 ? (string) $intValue : '';
    }

    private function formatDurationPart(mixed $value, string $part): string
    {
        if (! is_array($value)) {
            return '';
        }
        $durationValue = (int) ($value[$part] ?? 0);

        return $durationValue > 0 ? (string) $durationValue : '';
    }

    private function resolveSentenceDecisionStatus(string $outcomeTrack, string $terminationKind, string $specialOutcome, string $fallback): string
    {
        if ($outcomeTrack === 'termination') {
            if ($terminationKind === 'acquit') {
                return 'Цагаатгах';
            }
            if ($terminationKind === 'dismiss') {
                return 'Хэрэгсэхгүй болгох';
            }
        }

        if ($outcomeTrack === 'no_sentence' && $specialOutcome !== '') {
            return $specialOutcome;
        }

        if ($outcomeTrack === 'sentence') {
            return 'Ял оногдуулсан';
        }

        return $fallback;
    }

    private function formatJudgePanel(string $judgeNamesText): string
    {
        $names = collect(preg_split('/[\n,]+/u', $judgeNamesText) ?: [])
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn (string $name) => $name !== '')
            ->values()
            ->all();
        if ($names === []) {
            return '';
        }

        $parts = ['Даргалагч шүүгч: '.$names[0]];
        if (count($names) > 1) {
            $parts[] = 'Гишүүн шүүгч: '.implode(', ', array_slice($names, 1));
        }

        return implode('; ', $parts);
    }

    /**
     * @param  list<int>  $hearingIds
     * @return array<int, string>
     */
    private function loadJudgePanelByHearingId(array $hearingIds): array
    {
        if ($hearingIds === []) {
            return [];
        }

        $rows = DB::table('hearing_judges')
            ->join('users', 'users.id', '=', 'hearing_judges.judge_id')
            ->whereIn('hearing_judges.hearing_id', $hearingIds)
            ->orderBy('hearing_judges.hearing_id')
            ->orderBy('hearing_judges.position')
            ->get(['hearing_judges.hearing_id', 'hearing_judges.position', 'users.name']);

        $grouped = [];
        foreach ($rows as $row) {
            $hearingId = (int) $row->hearing_id;
            $grouped[$hearingId][] = [
                'position' => (int) $row->position,
                'name' => trim((string) $row->name),
            ];
        }

        $result = [];
        foreach ($grouped as $hearingId => $judges) {
            $presiding = collect($judges)->firstWhere('position', 1);
            $members = collect($judges)->filter(fn ($judge) => ($judge['position'] ?? 0) !== 1)->pluck('name')->filter()->values()->all();
            $parts = [];
            if (is_array($presiding) && ($presiding['name'] ?? '') !== '') {
                $parts[] = 'Даргалагч шүүгч: '.$presiding['name'];
            }
            if ($members !== []) {
                $parts[] = 'Гишүүн шүүгч: '.implode(', ', $members);
            }
            $result[$hearingId] = implode('; ', $parts);
        }

        return $result;
    }

    private function resolveProsecutorName(mixed $hearing): string
    {
        $textName = trim((string) ($hearing->prosecutor_name_text ?? $hearing->prosecutor_name ?? ''));
        if ($textName !== '') {
            return $textName;
        }

        $ids = [];
        if (is_array($hearing->prosecutor_ids ?? null)) {
            $ids = collect($hearing->prosecutor_ids)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->values()->all();
        } elseif (! empty($hearing->prosecutor_id)) {
            $ids = [(int) $hearing->prosecutor_id];
        }

        if ($ids === []) {
            return '';
        }

        return User::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->implode(', ');
    }

    private function formatLawyerSummary(mixed $hearing): string
    {
        $parts = [];
        $parts[] = $this->labeledList('ШүӨм', $hearing->defendant_lawyers_text ?? null);
        $parts[] = $this->labeledList('ХоӨм', $hearing->victim_lawyers_text ?? null);
        $parts[] = $this->labeledList('ИНӨм', $hearing->civil_plaintiff_lawyers ?? null);
        $parts[] = $this->labeledList('ИХӨм', $hearing->civil_defendant_lawyers ?? null);

        return implode('; ', array_filter($parts));
    }

    private function labeledList(string $label, mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        $items = array_values(array_filter(array_map(
            fn ($item) => trim((string) $item),
            $value
        ), fn (string $item) => $item !== ''));

        if ($items === []) {
            return '';
        }

        return $label.': '.implode(', ', $items);
    }

    /**
     * @return list<string>
     */
    private function extractFallbackDefendantNames(mixed $hearing): array
    {
        if (is_array($hearing->defendant_names ?? null) && $hearing->defendant_names !== []) {
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
    private function extractFallbackDefendantRegistries(mixed $hearing): array
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
