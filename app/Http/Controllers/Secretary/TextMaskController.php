<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\DocxTextMasker;
use Illuminate\Http\Request;

class TextMaskController extends Controller
{
    public function index()
    {
        return view('textmask.index', [
            'headerTitle' => 'Текст нууцлах',
            'actionUrl' => route('secretary.textmask.process'),
        ]);
    }

    public function downloadPreview(Request $request)
    {
        $path = (string)session('textmask.preview_path', '');
        $name = (string)session('textmask.preview_name', 'masked.docx');

        if ($path === '' || !is_file($path)) {
            return redirect()->route('secretary.textmask.index')->with('error', 'Урьдчилгаа файл олдсонгүй. Дахин нууцлаад урьдчилж хараарай.');
        }

        $request->session()->forget(['textmask.preview_path', 'textmask.preview_name']);

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }

    public function process(Request $request, DocxTextMasker $masker)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:51200'], // 50MB
            'phrases' => ['nullable', 'string'],
            'auto_phone' => ['nullable'],
            'auto_register' => ['nullable'],
            'auto_plate' => ['nullable'],
            'auto_initial_name' => ['nullable'],
            'mask_char' => ['nullable', 'string', 'max:1'],
            'action' => ['nullable', 'string'],
        ]);

        $phrases = preg_split("/\\r\\n|\\r|\\n/u", (string)($data['phrases'] ?? '')) ?: [];
        $auto = [
            'phone' => (bool)($data['auto_phone'] ?? false),
            'register' => (bool)($data['auto_register'] ?? false),
            'plate' => (bool)($data['auto_plate'] ?? false),
            'initial_name' => (bool)($data['auto_initial_name'] ?? false),
        ];
        $maskChar = $data['mask_char'] ?? '*';

        $uploaded = $request->file('file');
        $inPath = $uploaded->getRealPath();
        if (!$inPath) {
            return back()->with('error', 'Файлыг уншиж чадсангүй.');
        }

        $outName = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME) . '_masked.docx';
        $outPath = storage_path('app/tmp/' . uniqid('masked_', true) . '.docx');
        if (!is_dir(dirname($outPath))) {
            @mkdir(dirname($outPath), 0777, true);
        }

        try {
            $masker->mask($inPath, $outPath, $phrases, $auto, $maskChar);
        } catch (\Throwable $e) {
            return back()->with('error', 'Нууцлах үед алдаа гарлаа: ' . $e->getMessage());
        }

        if (($data['action'] ?? '') === 'preview') {
            try {
                $previewText = $masker->extractPlainText($outPath);
            } catch (\Throwable $e) {
                $previewText = '';
            }

            $request->session()->put([
                'textmask.preview_path' => $outPath,
                'textmask.preview_name' => $outName,
            ]);

            $maxChars = 30000;
            if (mb_strlen($previewText) > $maxChars) {
                $previewText = mb_substr($previewText, 0, $maxChars) . "\n...\n(урьдчилгаа таслагдсан)";
            }

            return view('textmask.preview', [
                'headerTitle' => 'Текст нууцлах',
                'previewText' => $previewText,
                'downloadUrl' => route('secretary.textmask.download'),
                'backUrl' => route('secretary.textmask.index'),
            ]);
        }

        return response()->download($outPath, $outName)->deleteFileAfterSend(true);
    }
}

