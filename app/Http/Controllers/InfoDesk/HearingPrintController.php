<?php

namespace App\Http\Controllers\InfoDesk;

use App\Http\Controllers\Concerns\ManagesHearingLogic;
use App\Http\Controllers\Controller;
use App\Models\Hearing;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HearingPrintController extends Controller
{
    use ManagesHearingLogic;

    private const EXPORT_LIMIT = 1000;

    public function index(Request $request)
    {
        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        // Огнооны интервал хайлт (YYYY-MM-DD)
        if ($request->filled('date_from')) {
            $query->whereDate('hearing_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('hearing_date', '<=', $request->input('date_to'));
        }

        $hearings = $query->paginate(20)->withQueryString();

        return view('hearings.print', [
            'hearings' => $hearings,
            'indexType' => 'info_desk',
            'headerTitle' => 'Хурлын зар хэвлэх',
            'listTitle' => 'Хурлын зарууд',
            'searchUrl' => route('info_desk.hearings.print'),
            'downloadUrl' => route('info_desk.hearings.print.download', $request->query()),
            'exportLimit' => self::EXPORT_LIMIT,
        ]);
    }

    public function download(Request $request)
    {
        if (!$request->filled('date_from') || !$request->filled('date_to')) {
            return redirect()->back()->with('error', 'Excel татахын өмнө эхлэх ба дуусах огноог заавал сонгоно уу.');
        }

        $query = Hearing::query()
            ->with(['judges', 'prosecutor'])
            ->orderBy('start_at', 'asc')
            ->orderBy('courtroom', 'asc');

        if ($request->filled('date_from')) {
            $query->whereDate('hearing_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('hearing_date', '<=', $request->input('date_to'));
        }

        $hearings = $query->limit(self::EXPORT_LIMIT)->get();

        $from = $request->input('date_from') ?: 'all';
        $to = $request->input('date_to') ?: 'all';
        $fileName = "hearings_{$from}_{$to}.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Хурлын зар');

        $headers = [
            'Огноо', 'Цаг', 'Танхим', 'Хэргийн дугаар',
            'Шүүх бүрэлдэхүүн болон шүүгчийн нэр', 'Улсын яллагч',
            'Шүүгдэгчийн нэр', 'Зүйл анги', 'Өмгөөлөгч',
            'ТСАХ', 'Хохирогч, гэрч, шинжээч, ххёт, иргэний нэхэмжлэгч, хариуцагч',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        $row = 2;
        foreach ($hearings as $h) {
            $dateStr = $h->hearing_date ? (is_object($h->hearing_date) ? $h->hearing_date->format('Y-m-d') : $h->hearing_date) : (optional($h->start_at)->format('Y-m-d') ?? '—');
            $timeStr = optional($h->start_at)->format('H:i') ?? ($h->hour !== null && $h->minute !== null ? sprintf('%02d:%02d', $h->hour, $h->minute) : '—');
            $judgesStr = $h->relationLoaded('judges') && $h->judges->isNotEmpty() ? $h->judges->pluck('name')->implode(', ') : ($h->judge_names_text ?? '—');
            $prosecutorStr = ($h->relationLoaded('prosecutor') && $h->prosecutor) ? $h->prosecutor->name : '—';
            $defendantsStr = is_array($h->defendant_names) ? implode("\n", $h->defendant_names) : ($h->defendants ?? $h->defendant_names ?? '—');
            if (!empty($h->hearing_state) && $h->hearing_state !== 'Хэвийн') {
                $defendantsStr .= ' (' . $h->hearing_state . ')';
            }
            $matterCategoriesStr = $h->relationLoaded('matterCategories')
                ? ($h->matterCategories->pluck('name')->implode("\n") ?: '—')
                : ($h->matterCategories()->pluck('name')->implode("\n") ?: '—');

            $lawyerLines = [];
            $def = is_array($h->defendant_lawyers_text) ? $h->defendant_lawyers_text : (is_string($h->defendant_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->defendant_lawyers_text))) : []);
            $victim = is_array($h->victim_lawyers_text) ? $h->victim_lawyers_text : (is_string($h->victim_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->victim_lawyers_text))) : []);
            $victimRep = is_array($h->victim_legal_rep_lawyers_text) ? $h->victim_legal_rep_lawyers_text : (is_string($h->victim_legal_rep_lawyers_text) ? array_filter(array_map('trim', explode(',', $h->victim_legal_rep_lawyers_text))) : []);
            $civilPl = is_array($h->civil_plaintiff_lawyers) ? $h->civil_plaintiff_lawyers : (is_string($h->civil_plaintiff_lawyers) ? array_filter(array_map('trim', explode(',', $h->civil_plaintiff_lawyers))) : []);
            $civilDef = is_array($h->civil_defendant_lawyers) ? $h->civil_defendant_lawyers : (is_string($h->civil_defendant_lawyers) ? array_filter(array_map('trim', explode(',', $h->civil_defendant_lawyers))) : []);
            if (count($def)) $lawyerLines[] = 'ШӨ: ' . implode(', ', $def);
            if (count($victim)) $lawyerLines[] = 'ХоӨ: ' . implode(', ', $victim);
            if (count($victimRep)) $lawyerLines[] = 'ХХЁТӨ: ' . implode(', ', $victimRep);
            if (count($civilPl)) $lawyerLines[] = 'ИНӨ: ' . implode(', ', $civilPl);
            if (count($civilDef)) $lawyerLines[] = 'ИХӨ: ' . implode(', ', $civilDef);
            $lawyersStr = count($lawyerLines) ? implode("\n", $lawyerLines) : '—';

            $preventiveStr = is_array($h->preventive_measure) ? implode("\n", $h->preventive_measure) : ($h->preventive_measure ?? '—');

            $otherLines = [];
            if (trim((string)($h->victim_name ?? '')) !== '') $otherLines[] = 'Хохирогч: ' . trim($h->victim_name);
            if (trim((string)($h->victim_legal_rep ?? '')) !== '') $otherLines[] = 'ХХЁТ: ' . trim($h->victim_legal_rep);
            if (trim((string)($h->witnesses ?? '')) !== '') $otherLines[] = 'Гэрч: ' . trim($h->witnesses);
            if (trim((string)($h->experts ?? '')) !== '') $otherLines[] = 'Шинжээч: ' . trim($h->experts);
            if (trim((string)($h->civil_plaintiff ?? '')) !== '') $otherLines[] = 'ИН: ' . trim($h->civil_plaintiff);
            if (trim((string)($h->civil_defendant ?? '')) !== '') $otherLines[] = 'ИХ: ' . trim($h->civil_defendant);
            $othersStr = count($otherLines) ? implode("\n", $otherLines) : '—';

            $sheet->fromArray([
                $dateStr,
                $timeStr,
                $h->courtroom ?? '—',
                $h->case_no ?? '—',
                $judgesStr,
                $prosecutorStr,
                $defendantsStr,
                $matterCategoriesStr,
                $lawyersStr,
                $preventiveStr,
                $othersStr,
            ], null, 'A' . $row);
            $row++;
        }

        $lastRow = max(2, $row - 1);
        $sheet->getStyle('A1:K' . $lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}

