<?php

namespace App\Support;

use App\Models\Hearing;
use Illuminate\Support\Collection;

class HearingSummaryDisplay
{
    /**
     * @param  Collection<int, string>  $matterNamesById
     * @return array{
     *     date: string,
     *     time: string,
     *     courtroom: string,
     *     case_no: string,
     *     judges: string,
     *     prosecutor: string,
     *     defendants: string,
     *     matter_categories: list<string>,
     *     lawyers: list<string>,
     *     preventive: string,
     *     other_parties: list<string>,
     *     decision_summary: string,
     *     decided_matter_lines: list<string>,
     *     decision_status: string,
     *     decision_status_key: string,
     * }
     */
    public static function row(Hearing $hearing, Collection $matterNamesById): array
    {
        $date = $hearing->hearing_date
            ? (is_object($hearing->hearing_date) ? $hearing->hearing_date->format('Y-m-d') : (string) $hearing->hearing_date)
            : (optional($hearing->start_at)->format('Y-m-d') ?? '—');

        $time = optional($hearing->start_at)->format('H:i')
            ?? ($hearing->hour !== null && $hearing->minute !== null
                ? sprintf('%02d:%02d', (int) $hearing->hour, (int) $hearing->minute)
                : '—');

        $judges = $hearing->relationLoaded('judges') && $hearing->judges->isNotEmpty()
            ? $hearing->judges->pluck('name')->implode(', ')
            : (trim((string) ($hearing->judge_names_text ?? '')) ?: '—');

        $prosecutor = self::prosecutorLabel($hearing);

        $defendants = is_array($hearing->defendant_names)
            ? implode(', ', $hearing->defendant_names)
            : (trim((string) ($hearing->defendants ?? $hearing->defendant_names ?? '')) ?: '—');

        $matterCategories = collect($hearing->matter_category_ids ?? [])
            ->map(fn ($id) => $matterNamesById->get((int) $id))
            ->filter()
            ->values()
            ->all();

        $preventive = is_array($hearing->preventive_measure)
            ? implode(', ', $hearing->preventive_measure)
            : (trim((string) ($hearing->preventive_measure ?? '')) ?: '—');

        $decisionStatusKey = trim((string) ($hearing->notes_decision_status ?? ''));
        $decidedMatterLines = collect(preg_split('/[,;\n]+/u', (string) ($hearing->notes_decided_matter ?? '')))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();

        return [
            'date' => $date,
            'time' => $time,
            'courtroom' => trim((string) ($hearing->courtroom ?? '')) ?: '—',
            'case_no' => trim((string) ($hearing->case_no ?? '')) ?: '—',
            'judges' => $judges,
            'prosecutor' => $prosecutor,
            'defendants' => $defendants,
            'matter_categories' => $matterCategories,
            'lawyers' => self::lawyerLines($hearing),
            'preventive' => $preventive,
            'other_parties' => self::otherPartyLines($hearing),
            'decision_summary' => trim((string) ($hearing->notes_handover_text ?? '')) ?: '—',
            'decided_matter_lines' => $decidedMatterLines,
            'decision_status' => $decisionStatusKey !== '' ? $decisionStatusKey : 'Хүлээгдэж буй',
            'decision_status_key' => $decisionStatusKey,
        ];
    }

    public static function decisionStatusBadgeClass(string $statusKey): string
    {
        return match ($statusKey) {
            'Шийдвэрлэсэн' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'Хойшилсон' => 'bg-amber-100 text-amber-800 border-amber-200',
            'Завсарласан' => 'bg-sky-100 text-sky-800 border-sky-200',
            'Түдгэлзүүлсэн' => 'bg-orange-100 text-orange-800 border-orange-200',
            'Прокурорт буцаасан' => 'bg-rose-100 text-rose-800 border-rose-200',
            'Яллагдагчийг шүүхэд шилжүүлсэн' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            '60 хүртэлх хоногоор хойшлуулсан' => 'bg-violet-100 text-violet-800 border-violet-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

    private static function prosecutorLabel(Hearing $hearing): string
    {
        if ($hearing->relationLoaded('prosecutor') && $hearing->prosecutor) {
            return $hearing->prosecutor->name;
        }

        $ids = $hearing->prosecutor_ids_list;
        if ($ids !== []) {
            $names = \App\Models\User::query()->whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
            if ($names !== []) {
                return implode(', ', $names);
            }
        }

        return trim((string) ($hearing->prosecutor_name ?? '')) ?: '—';
    }

    /**
     * @return list<string>
     */
    private static function lawyerLines(Hearing $hearing): array
    {
        $def = self::stringList($hearing->defendant_lawyers_text ?? $hearing->defendant_lawyers);
        $victim = self::stringList($hearing->victim_lawyers_text ?? $hearing->victim_lawyers);
        $victimRep = self::stringList($hearing->victim_legal_rep_lawyers_text ?? $hearing->victim_legal_rep_lawyers);
        $civilPl = self::stringList($hearing->civil_plaintiff_lawyers);
        $civilDef = self::stringList($hearing->civil_defendant_lawyers);

        $lines = [];
        if ($def !== []) {
            $lines[] = 'ШүӨм: '.implode(', ', $def);
        }
        if ($victim !== []) {
            $lines[] = 'ХоӨм: '.implode(', ', $victim);
        }
        if ($victimRep !== []) {
            $lines[] = 'ХХЁТ-ийн Өм: '.implode(', ', $victimRep);
        }
        if ($civilPl !== []) {
            $lines[] = 'ИНӨм: '.implode(', ', $civilPl);
        }
        if ($civilDef !== []) {
            $lines[] = 'ИХӨм: '.implode(', ', $civilDef);
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function otherPartyLines(Hearing $hearing): array
    {
        $lines = [];
        if (trim((string) ($hearing->victim_name ?? '')) !== '') {
            $lines[] = 'Хохирогч: '.trim((string) $hearing->victim_name);
        }
        if (trim((string) ($hearing->victim_legal_rep ?? '')) !== '') {
            $lines[] = 'ХХЁТ: '.trim((string) $hearing->victim_legal_rep);
        }
        if (trim((string) ($hearing->witnesses ?? '')) !== '') {
            $lines[] = 'Гэрч: '.trim((string) $hearing->witnesses);
        }
        if (trim((string) ($hearing->experts ?? '')) !== '') {
            $lines[] = 'Шинжээч: '.trim((string) $hearing->experts);
        }
        if (trim((string) ($hearing->civil_plaintiff ?? '')) !== '') {
            $lines[] = 'ИН: '.trim((string) $hearing->civil_plaintiff);
        }
        if (trim((string) ($hearing->civil_defendant ?? '')) !== '') {
            $lines[] = 'ИХ: '.trim((string) $hearing->civil_defendant);
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }
}
