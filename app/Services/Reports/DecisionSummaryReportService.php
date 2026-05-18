<?php

namespace App\Services\Reports;

use App\Models\Hearing;
use App\Support\HearingSummaryDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DecisionSummaryReportService
{
    /**
     * @return list<array{key: string, label: string}>
     */
    public function exportColumns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Огноо'],
            ['key' => 'time', 'label' => 'Цаг'],
            ['key' => 'courtroom', 'label' => 'Танхим'],
            ['key' => 'case_no', 'label' => 'Хэргийн дугаар'],
            ['key' => 'judges', 'label' => 'Шүүх бүрэлдэхүүн болон шүүгчийн нэр'],
            ['key' => 'prosecutor', 'label' => 'Улсын яллагч'],
            ['key' => 'defendants', 'label' => 'Шүүгдэгчийн нэр'],
            ['key' => 'matter_categories', 'label' => 'Зүйл анги'],
            ['key' => 'lawyers', 'label' => 'Өмгөөлөгчийн нэр'],
            ['key' => 'preventive', 'label' => 'ТСАХ'],
            ['key' => 'other_parties', 'label' => 'Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч'],
            ['key' => 'decision_summary', 'label' => 'Шийдвэрийн тойм'],
            ['key' => 'decided_matter', 'label' => 'Шийдвэрлэсэн зүйл анги'],
            ['key' => 'decision_status', 'label' => 'Шүүх хуралдааны шийдвэр'],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    public function buildExportRows(Builder $query, Collection $matterNamesById, int $limit): array
    {
        $hearings = (clone $query)
            ->with(['judges', 'prosecutor'])
            ->orderBy('hearing_date')
            ->orderBy('hour')
            ->orderBy('minute')
            ->orderBy('courtroom')
            ->limit($limit)
            ->get();

        return $hearings
            ->map(fn (Hearing $hearing) => $this->flattenRow(HearingSummaryDisplay::row($hearing, $matterNamesById)))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, string>
     */
    private function flattenRow(array $row): array
    {
        return [
            'date' => (string) $row['date'],
            'time' => (string) $row['time'],
            'courtroom' => (string) $row['courtroom'],
            'case_no' => (string) $row['case_no'],
            'judges' => (string) $row['judges'],
            'prosecutor' => (string) $row['prosecutor'],
            'defendants' => (string) $row['defendants'],
            'matter_categories' => $this->joinLines($row['matter_categories'] ?? []),
            'lawyers' => $this->joinLines($row['lawyers'] ?? []),
            'preventive' => (string) $row['preventive'],
            'other_parties' => $this->joinLines($row['other_parties'] ?? []),
            'decision_summary' => (string) $row['decision_summary'],
            'decided_matter' => $this->joinLines($row['decided_matter_lines'] ?? []),
            'decision_status' => (string) $row['decision_status'],
        ];
    }

    /**
     * @param  list<string>  $lines
     */
    private function joinLines(array $lines): string
    {
        $filtered = array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));

        return $filtered === [] ? '—' : implode("\n", $filtered);
    }
}
