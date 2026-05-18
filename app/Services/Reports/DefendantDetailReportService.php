<?php

namespace App\Services\Reports;

use App\Models\MatterCategory;
use App\Models\User;
use App\Support\DefendantRegistryResolver;
use App\Support\MongolianRegistryDemographics;
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
                $decidedMatterIds = $this->resolveDecidedMatterIdsForSentence($sentence);
                /** @var list<int|null> $rowMatterIds null = нэг мөр, шийдвэрлэсэн зүйл анги байхгүй */
                $rowMatterIds = $decidedMatterIds === [] ? [null] : $decidedMatterIds;

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

                $specialOutcome = (string) ($sentence['special_outcome'] ?? '');
                $terminationKind = (string) ($sentence['termination_kind'] ?? '');
                $terminationNote = trim((string) ($sentence['termination_note'] ?? ''));
                $outcomeTrack = (string) ($sentence['outcome_track'] ?? '');
                $matterDecisions = collect($sentence['matter_decisions'] ?? [])
                    ->filter(fn ($row) => is_array($row))
                    ->values()
                    ->all();
                $defendantRegistry = DefendantRegistryResolver::registryForSentence(
                    $sentence,
                    $hearing,
                    $sentenceIndex
                );
                $demographics = MongolianRegistryDemographics::parse(
                    $defendantRegistry,
                    $hearing->hearing_date
                );
                $lawyers = $this->formatLawyerSummary($hearing);
                $judgePanel = $judgePanelByHearingId[(int) $hearing->id]
                    ?? $this->formatJudgePanel((string) ($hearing->judge_names_text ?? ''));
                $prosecutorName = $this->resolveProsecutorName($hearing);
                $defendantLabel = trim((string) ($sentence['defendant_name'] ?? '')) !== ''
                    ? (string) $sentence['defendant_name']
                    : (string) ($fallbackDefendantNames[$sentenceIndex] ?? '');

                foreach ($rowMatterIds as $matterId) {
                    $matterIdInt = $matterId === null ? null : (int) $matterId;
                    $punishments = $this->punishmentsForDecidedMatter($sentence, $matterIdInt);
                    $matterDecisionType = $matterIdInt !== null
                        ? $this->matterDecisionTypeForMatterId($matterDecisions, $matterIdInt)
                        : null;
                    $decidedMatterLabel = ($matterIdInt !== null && $matterIdInt > 0)
                        ? (string) ($matterMap[$matterIdInt] ?? '')
                        : '';
                    $decisionStatus = $this->resolveRowDecisionLabel(
                        $matterIdInt,
                        $matterDecisionType,
                        $outcomeTrack,
                        $terminationKind,
                        $specialOutcome,
                        (string) ($hearing->notes_decision_status ?? '')
                    );
                    $useSpecialOutcomeFlags = $matterDecisionType === 'no_sentence'
                        || ($matterIdInt === null && $outcomeTrack === 'no_sentence' && $specialOutcome !== '');
                    $acquitActive = $matterDecisionType === 'acquit'
                        || ($matterIdInt === null && $terminationKind === 'acquit');
                    $dismissActive = $matterDecisionType === 'dismiss'
                        || ($matterIdInt === null && $terminationKind === 'dismiss');

                    $rows[] = [
                        'hearing_id' => (int) $hearing->id,
                        'case_no' => (string) ($hearing->case_no ?? ''),
                        'hearing_state' => (string) ($hearing->hearing_state ?? ''),
                        'hearing_date' => (string) ($hearing->hearing_date ?? ''),
                        'hearing_time' => $hearing->start_at ? (string) Carbon::parse($hearing->start_at)->format('H:i') : '',
                        'courtroom' => (string) ($hearing->courtroom ?? ''),
                        'judge_panel' => $judgePanel,
                        'defendant_name' => $defendantLabel,
                        'victim_name' => (string) ($hearing->victim_name ?? ''),
                        'victim_legal_rep' => (string) ($hearing->victim_legal_rep ?? ''),
                        'preventive_measure' => is_array($hearing->preventive_measure ?? null)
                            ? implode(', ', array_filter(array_map(fn ($value) => trim((string) $value), $hearing->preventive_measure)))
                            : (string) ($hearing->preventive_measure ?? ''),
                        'prosecutor_name' => $prosecutorName,
                        'incoming_matter' => ($matterIdInt !== null && $matterIdInt > 0)
                            ? (string) ($matterMap[$matterIdInt] ?? '')
                            : ($allMatterNames !== []
                                ? implode(', ', $allMatterNames)
                                : (string) ($hearing->matter_category ?? '')),
                        'defender_summary' => $lawyers,
                        'witness_names' => (string) ($hearing->witnesses ?? ''),
                        'expert_names' => (string) ($hearing->experts ?? ''),
                        'civil_plaintiff' => (string) ($hearing->civil_plaintiff ?? ''),
                        'civil_defendant' => (string) ($hearing->civil_defendant ?? ''),
                        'notes_handover_text' => (string) ($hearing->notes_handover_text ?? ''),
                        'notes_clerk_name' => (string) ($clerkMap[(int) ($hearing->clerk_id ?? 0)] ?? ''),
                        'decision_status' => $decisionStatus,
                        'decided_matter' => $decidedMatterLabel,
                        'acquit' => $acquitActive ? ($terminationNote !== '' ? $terminationNote : 'Тийм') : '',
                        'dismiss' => $dismissActive ? ($terminationNote !== '' ? $terminationNote : 'Тийм') : '',
                        'release_from_criminal_liability' => ($useSpecialOutcomeFlags && $specialOutcome === 'Эрүүгийн хариуцлагаас чөлөөлсөн') ? 'Тийм' : '',
                        'medical_measure' => ($useSpecialOutcomeFlags && $specialOutcome === 'Эмнэлгийн чанартай албадлагын арга хэмжээ хэрэглэсэн') ? 'Тийм' : '',
                        'probation_without_imprisonment' => ($useSpecialOutcomeFlags && $specialOutcome === 'Хорих ял оногдуулахгүйгээр тэнссэн') ? 'Тийм' : '',
                        'educational_measure' => ($useSpecialOutcomeFlags && $specialOutcome === 'Хүмүүжлийн чанартай албадлагын арга хэмжээ хэрэглэсэн') ? 'Тийм' : '',
                        'imprisonment_closed' => $this->formatYearsMonths($punishments['imprisonment_closed'] ?? null),
                        'imprisonment_open' => $this->formatYearsMonths($punishments['imprisonment_open'] ?? null),
                        'community_service' => $this->formatInteger($punishments['community_service']['hours'] ?? null),
                        'travel_restriction_years' => $this->formatDurationPart($punishments['travel_restriction'] ?? null, 'years'),
                        'travel_restriction_months' => $this->formatDurationPart($punishments['travel_restriction'] ?? null, 'months'),
                        'fine_units' => $this->formatInteger($punishments['fine']['fine_units'] ?? null),
                        'rights_ban_driving_years' => $this->formatDurationPart($punishments['rights_ban_driving'] ?? null, 'years'),
                        'rights_ban_driving_months' => $this->formatDurationPart($punishments['rights_ban_driving'] ?? null, 'months'),
                        'rights_ban_professional_activity_years' => $this->formatDurationPart($punishments['rights_ban_professional_activity'] ?? null, 'years'),
                        'rights_ban_professional_activity_months' => $this->formatDurationPart($punishments['rights_ban_professional_activity'] ?? null, 'months'),
                        'rights_ban_public_service_years' => $this->formatDurationPart($punishments['rights_ban_public_service'] ?? null, 'years'),
                        'rights_ban_public_service_months' => $this->formatDurationPart($punishments['rights_ban_public_service'] ?? null, 'months'),
                        'damage_amount' => $this->formatInteger($punishments['damage_amount'] ?? null),
                        'compensated_damage_amount' => $this->formatInteger($punishments['compensated_damage_amount'] ?? null),
                        'asset_confiscation' => ! empty($punishments['asset_confiscation']) ? 'Тийм' : '',
                        'destroy_evidence' => ! empty($punishments['destroy_evidence']) ? 'Тийм' : '',
                        'other_punishment' => (string) ($punishments['other'] ?? ''),
                        'defendant_age' => isset($demographics['age']) ? (string) $demographics['age'] : '',
                        'defendant_gender' => $demographics['gender'] ?? '',
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Вэб дэлгэрэнгүй хүснэгтийн rowspan бүлэглэлт (хэрэг / шүүгдэгч / зүйл анги багана).
     *
     * @return array{case: list<string>, defendant: list<string>, matter: list<string>}
     */
    public function uiRowspanColumnGroups(): array
    {
        $caseKeys = [
            'case_no',
            'hearing_state',
            'hearing_date',
            'hearing_time',
            'courtroom',
            'judge_panel',
            'victim_name',
            'victim_legal_rep',
            'preventive_measure',
            'prosecutor_name',
            'defender_summary',
            'witness_names',
            'expert_names',
            'civil_plaintiff',
            'civil_defendant',
            'notes_handover_text',
            'notes_clerk_name',
        ];
        $defendantKeys = ['defendant_name', 'defendant_age', 'defendant_gender'];
        $orderedKeys = array_column($this->exportColumns(), 'key');
        $matterKeys = array_values(array_filter(
            $orderedKeys,
            fn (string $key): bool => ! in_array($key, $caseKeys, true) && ! in_array($key, $defendantKeys, true)
        ));

        return [
            'case' => $caseKeys,
            'defendant' => $defendantKeys,
            'matter' => $matterKeys,
        ];
    }

    /**
     * Хэрэг (хурал) → шүүгдэгч → зүйл анги гэсэн rowspan-тай дэлгэрэнгүй хүснэгтэнд зориулсан бүлэг.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{case_summary: string, case_row: array<string, mixed>, rowspan_case: int, defendants: list<array{name: string, row: array<string, mixed>, rowspan: int, matters: list<array{decided_matter: string, summary: string, row: array<string, mixed>}>}>}>
     */
    public function buildNestedPreviewGroups(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $hearingOrder = [];
        $byHearing = [];
        foreach ($rows as $row) {
            $hid = (string) ((int) ($row['hearing_id'] ?? 0));
            if (! isset($byHearing[$hid])) {
                $byHearing[$hid] = [];
                $hearingOrder[] = $hid;
            }
            $byHearing[$hid][] = $row;
        }

        $out = [];
        foreach ($hearingOrder as $hid) {
            $hearingRows = $byHearing[$hid];
            $firstRow = $hearingRows[0];
            $caseSummary = $this->formatCaseSummaryCell($firstRow);

            $defendantOrder = [];
            $byDefendant = [];
            foreach ($hearingRows as $row) {
                $dname = trim((string) ($row['defendant_name'] ?? ''));
                if ($dname === '') {
                    $dname = '—';
                }
                if (! isset($byDefendant[$dname])) {
                    $byDefendant[$dname] = [];
                    $defendantOrder[] = $dname;
                }
                $byDefendant[$dname][] = $row;
            }

            $defendants = [];
            foreach ($defendantOrder as $dname) {
                $defRows = $byDefendant[$dname];
                $matters = [];
                foreach ($defRows as $r) {
                    $matters[] = [
                        'decided_matter' => trim((string) ($r['decided_matter'] ?? '')),
                        'summary' => $this->summarizeDecisionCell($r),
                        'row' => $r,
                    ];
                }
                $defendants[] = [
                    'name' => $dname,
                    'row' => $defRows[0],
                    'rowspan' => count($matters),
                    'matters' => $matters,
                ];
            }

            $out[] = [
                'case_summary' => $caseSummary,
                'case_row' => $firstRow,
                'rowspan_case' => count($hearingRows),
                'defendants' => $defendants,
            ];
        }

        return $out;
    }

    /**
     * Бүх баганатай хүснэгтэд зориулж шүүгдэгч болон шийдвэрлэсэн зүйл ангийн rowspan-ийг тооцно (_ui_* түлхүүрүүд).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function annotateRowspanForUiTable(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $n = count($rows);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $row = $rows[$i];
            $hid = (string) ((int) ($row['hearing_id'] ?? 0));
            $dname = (string) ($row['defendant_name'] ?? '');
            $matter = (string) ($row['decided_matter'] ?? '');

            $defSpan = 1;
            for ($j = $i + 1; $j < $n; $j++) {
                $rj = $rows[$j];
                if ((string) ((int) ($rj['hearing_id'] ?? 0)) !== $hid) {
                    break;
                }
                if ((string) ($rj['defendant_name'] ?? '') !== $dname) {
                    break;
                }
                $defSpan++;
            }
            $defShow = $i === 0
                || (string) ((int) ($rows[$i - 1]['hearing_id'] ?? 0)) !== $hid
                || (string) ($rows[$i - 1]['defendant_name'] ?? '') !== $dname;

            $matterSpan = 1;
            for ($j = $i + 1; $j < $n; $j++) {
                $rj = $rows[$j];
                if ((string) ((int) ($rj['hearing_id'] ?? 0)) !== $hid) {
                    break;
                }
                if ((string) ($rj['defendant_name'] ?? '') !== $dname) {
                    break;
                }
                if ((string) ($rj['decided_matter'] ?? '') !== $matter) {
                    break;
                }
                $matterSpan++;
            }
            $matterShow = $i === 0
                || (string) ((int) ($rows[$i - 1]['hearing_id'] ?? 0)) !== $hid
                || (string) ($rows[$i - 1]['defendant_name'] ?? '') !== $dname
                || (string) ($rows[$i - 1]['decided_matter'] ?? '') !== $matter;

            $row['_ui_defendant_rowspan'] = $defSpan;
            $row['_ui_defendant_show'] = $defShow;
            $row['_ui_decided_matter_rowspan'] = $matterSpan;
            $row['_ui_decided_matter_show'] = $matterShow;

            $out[] = $row;
        }

        return $out;
    }

    private function formatCaseSummaryCell(array $row): string
    {
        $parts = [];
        $caseNo = trim((string) ($row['case_no'] ?? ''));
        if ($caseNo !== '') {
            $parts[] = 'Хэргийн дугаар: '.$caseNo;
        }
        $date = trim((string) ($row['hearing_date'] ?? ''));
        $time = trim((string) ($row['hearing_time'] ?? ''));
        $when = trim($date.' '.$time);
        if ($when !== '') {
            $parts[] = 'Огноо, цаг: '.$when;
        }
        $room = trim((string) ($row['courtroom'] ?? ''));
        if ($room !== '') {
            $parts[] = 'Танхим: '.$room;
        }
        $state = trim((string) ($row['hearing_state'] ?? ''));
        if ($state !== '') {
            $parts[] = 'Төлөв: '.$state;
        }
        $judges = trim((string) ($row['judge_panel'] ?? ''));
        if ($judges !== '') {
            $parts[] = $judges;
        }

        return $parts === [] ? '—' : implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function summarizeDecisionCell(array $row): string
    {
        $lines = [];
        $status = trim((string) ($row['decision_status'] ?? ''));
        if ($status !== '') {
            $lines[] = $status;
        }
        if (($row['fine_units'] ?? '') !== '') {
            $lines[] = 'Торгох (нэгж): '.$row['fine_units'];
        }
        if (($row['imprisonment_open'] ?? '') !== '') {
            $lines[] = 'Хорих (нээлттэй): '.$row['imprisonment_open'];
        }
        if (($row['imprisonment_closed'] ?? '') !== '') {
            $lines[] = 'Хорих (хаалттай): '.$row['imprisonment_closed'];
        }
        if (($row['community_service'] ?? '') !== '') {
            $lines[] = 'Нийтэд тустай ажил: '.$row['community_service'].' цаг';
        }
        $ty = trim((string) ($row['travel_restriction_years'] ?? ''));
        $tm = trim((string) ($row['travel_restriction_months'] ?? ''));
        if ($ty !== '' || $tm !== '') {
            $lines[] = 'Зорчих эрх: '.$ty.($ty !== '' && $tm !== '' ? '/' : '').$tm;
        }
        $rdy = trim((string) ($row['rights_ban_driving_years'] ?? ''));
        $rdm = trim((string) ($row['rights_ban_driving_months'] ?? ''));
        if ($rdy !== '' || $rdm !== '') {
            $lines[] = 'Жолоодох эрх хасах: '.$rdy.($rdy !== '' && $rdm !== '' ? '/' : '').$rdm;
        }
        $rpy = trim((string) ($row['rights_ban_professional_activity_years'] ?? ''));
        $rpm = trim((string) ($row['rights_ban_professional_activity_months'] ?? ''));
        if ($rpy !== '' || $rpm !== '') {
            $lines[] = 'Мэргэжлийн эрх хасах: '.$rpy.($rpy !== '' && $rpm !== '' ? '/' : '').$rpm;
        }
        $rpuy = trim((string) ($row['rights_ban_public_service_years'] ?? ''));
        $rpum = trim((string) ($row['rights_ban_public_service_months'] ?? ''));
        if ($rpuy !== '' || $rpum !== '') {
            $lines[] = 'Нийтийн албаны эрх хасах: '.$rpuy.($rpuy !== '' && $rpum !== '' ? '/' : '').$rpum;
        }
        if (($row['damage_amount'] ?? '') !== '') {
            $lines[] = 'Хохирлын дүн: '.$row['damage_amount'];
        }
        if (($row['compensated_damage_amount'] ?? '') !== '') {
            $lines[] = 'Нөхөн төлүүлсэн: '.$row['compensated_damage_amount'];
        }
        if (($row['asset_confiscation'] ?? '') === 'Тийм') {
            $lines[] = 'Хөрөнгө хураах: Тийм';
        }
        if (($row['destroy_evidence'] ?? '') === 'Тийм') {
            $lines[] = 'Баримт устгуулах: Тийм';
        }
        $other = trim((string) ($row['other_punishment'] ?? ''));
        if ($other !== '') {
            $lines[] = 'Бусад: '.$other;
        }
        $acquit = trim((string) ($row['acquit'] ?? ''));
        if ($acquit !== '') {
            $lines[] = 'Цагаатгах: '.$acquit;
        }
        $dismiss = trim((string) ($row['dismiss'] ?? ''));
        if ($dismiss !== '') {
            $lines[] = 'Хэрэгсэхгүй: '.$dismiss;
        }
        foreach ([
            'release_from_criminal_liability' => 'Эрүүгийн хариуцлагаас чөлөөлсөн',
            'medical_measure' => 'Эмнэлгийн албадлага',
            'probation_without_imprisonment' => 'Хорихгүйгээр тэнссэн',
            'educational_measure' => 'Хүмүүжлийн албадлага',
        ] as $key => $label) {
            if (($row[$key] ?? '') === 'Тийм') {
                $lines[] = $label.': Тийм';
            }
        }

        return $lines === [] ? '—' : implode("\n", $lines);
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

    /**
     * @param  array<string, mixed>  $sentence
     * @return list<int>
     */
    private function resolveDecidedMatterIdsForSentence(array $sentence): array
    {
        $ids = collect($sentence['decided_matter_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            $ids = collect($sentence['allocations'] ?? [])
                ->map(fn ($row) => is_array($row) ? (int) ($row['matter_category_id'] ?? 0) : 0)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if ($ids === []) {
            $ids = collect($sentence['matter_decisions'] ?? [])
                ->filter(fn ($row) => is_array($row))
                ->map(fn ($row) => (int) ($row['matter_category_id'] ?? 0))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $sentence
     * @return array<string, mixed>
     */
    private function punishmentsForDecidedMatter(array $sentence, ?int $matterId): array
    {
        $topLevel = is_array($sentence['punishments'] ?? null) ? $sentence['punishments'] : [];
        if ($matterId === null || $matterId < 1) {
            return $topLevel;
        }

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
            $t = trim((string) ($row['decision_type'] ?? ''));
            if (in_array($t, ['sentence', 'no_sentence', 'dismiss', 'acquit'], true)) {
                return $t;
            }
        }

        return null;
    }

    private function resolveRowDecisionLabel(
        ?int $matterId,
        ?string $matterDecisionType,
        string $outcomeTrack,
        string $terminationKind,
        string $specialOutcome,
        string $hearingDecisionFallback
    ): string {
        if ($matterId !== null && $matterId > 0 && $matterDecisionType !== null) {
            if ($matterDecisionType === 'acquit') {
                return 'Цагаатгах';
            }
            if ($matterDecisionType === 'dismiss') {
                return 'Хэрэгсэхгүй болгох';
            }
            if ($matterDecisionType === 'no_sentence') {
                return $specialOutcome !== ''
                    ? $specialOutcome
                    : 'Ял оногдуулахгүйгээр тэнсэх';
            }
            if ($matterDecisionType === 'sentence') {
                return 'Ял оногдуулсан';
            }
        }

        return $this->resolveSentenceDecisionStatus($outcomeTrack, $terminationKind, $specialOutcome, $hearingDecisionFallback);
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
