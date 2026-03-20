<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocxTextMasker
{
    /**
     * Mask text inside a .docx file by replacing exact phrases and applying regex masks.
     *
     * @param  string  $inputPath  Existing .docx file path
     * @param  string  $outputPath Output .docx file path (will be overwritten)
     * @param  array<int,string>  $phrases Exact phrases to mask (case-sensitive)
     * @param  array<string,bool> $autoOptions
     * @param  string  $maskChar Character used to mask (repeated)
     */
    public function mask(string $inputPath, string $outputPath, array $phrases, array $autoOptions = [], string $maskChar = '*'): void
    {
        if (!is_file($inputPath)) {
            throw new RuntimeException('Input file not found.');
        }
        if (!copy($inputPath, $outputPath)) {
            throw new RuntimeException('Failed to prepare output file.');
        }

        $zip = new ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new RuntimeException('Unable to open docx as zip.');
        }

        // word/document.xml + headers/footers
        $targets = ['word/document.xml'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (is_string($name) && preg_match('#^word/(header|footer)\\d+\\.xml$#', $name)) {
                $targets[] = $name;
            }
        }
        $targets = array_values(array_unique($targets));

        foreach ($targets as $path) {
            $xml = $zip->getFromName($path);
            if ($xml === false) continue;

            $updated = $this->maskWordXml($xml, $phrases, $autoOptions, $maskChar);
            $zip->addFromString($path, $updated);
        }

        $zip->close();
    }

    /**
     * Best-effort: extract visible plain text from a .docx (document.xml only).
     */
    public function extractPlainText(string $docxPath): string
    {
        if (!is_file($docxPath)) {
            throw new RuntimeException('Docx file not found.');
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('Unable to open docx as zip.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        if (!@$doc->loadXML($xml)) {
            return '';
        }

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Join paragraphs with newline; join runs within paragraph with empty string
        $paras = $xp->query('//w:p');
        if (!$paras) {
            return '';
        }

        $out = [];
        foreach ($paras as $p) {
            $texts = $xp->query('.//w:t', $p);
            if (!$texts) {
                continue;
            }
            $line = '';
            foreach ($texts as $t) {
                $line .= $t->nodeValue ?? '';
            }
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }

    private function maskWordXml(string $xml, array $phrases, array $autoOptions, string $maskChar): string
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;

        // Suppress XML warnings from Word's namespaces.
        if (!@$doc->loadXML($xml)) {
            return $xml;
        }

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $nodes = $xp->query('//w:t');
        if (!$nodes) return $doc->saveXML() ?: $xml;

        $phrases = array_values(array_filter(array_map('trim', $phrases), fn ($v) => $v !== ''));
        $quoteChars = "\"'“”«»";
        $expandedPhrases = [];
        foreach ($phrases as $p) {
            $expandedPhrases[] = $p;
            // If user includes quotes in the phrase, also try unquoted version.
            $unquoted = trim($p, $quoteChars . " \t\n\r\0\x0B");
            if ($unquoted !== '' && $unquoted !== $p) {
                $expandedPhrases[] = $unquoted;
            }
        }
        $phrases = array_values(array_unique($expandedPhrases));

        foreach ($nodes as $node) {
            $text = $node->nodeValue ?? '';
            if ($text === '') continue;

            // Exact phrase masks
            foreach ($phrases as $p) {
                if ($p === '') continue;

                // Case-insensitive, Unicode-safe replacement (Cyrillic/Latin/etc).
                $quoted = preg_quote($p, '/');
                $text = preg_replace_callback('/' . $quoted . '/iu', function ($m) use ($maskChar) {
                    $s = $m[0] ?? '';
                    return str_repeat($maskChar, mb_strlen($s));
                }, $text) ?? $text;

                // Also handle the same phrase wrapped in matching quotes: "phrase", “phrase”, 'phrase', «phrase»
                $text = preg_replace_callback('/([' . preg_quote($quoteChars, '/') . '])\\s*(' . $quoted . ')\\s*\\1/iu', function ($m) use ($maskChar) {
                    $q = $m[1] ?? '"';
                    $inner = $m[2] ?? '';
                    return $q . str_repeat($maskChar, mb_strlen($inner)) . $q;
                }, $text) ?? $text;
            }

            // Auto masks (simple, best-effort)
            if (!empty($autoOptions['phone'])) {
                // Монгол утас (8 оронтой, 9xxxxxxxx / 8xxxxxxxx / 7xxxxxxx гэх мэт), мөн +976
                $text = preg_replace('/(?:\\+?976\\s*)?(\\b\\d{8}\\b)/u', str_repeat($maskChar, 8), $text) ?? $text;
            }
            if (!empty($autoOptions['register'])) {
                // Регистр: 2 кирилл үсэг + 8 оронтой тоо (ж: АБ99112233)
                $text = preg_replace('/\\b[А-ЯӨҮ]{2}\\d{8}\\b/u', str_repeat($maskChar, 10), $text) ?? $text;
            }
            if (!empty($autoOptions['plate'])) {
                // Машины дугаар (best-effort):
                // - 1234ABC / 1234 ABC
                // - 12-34ABC / 12-34 ABC
                $text = preg_replace_callback('/\\b(?:(\\d{4})(\\s?)([A-ZА-ЯӨҮ]{3})|(\\d{2})-(\\d{2})(\\s?)([A-ZА-ЯӨҮ]{3}))\\b/u', function ($m) use ($maskChar) {
                    if (!empty($m[1])) {
                        $space = $m[2] ?? '';
                        return str_repeat($maskChar, 4) . $space . str_repeat($maskChar, 3);
                    }

                    $space = $m[6] ?? '';
                    return str_repeat($maskChar, 2) . '-' . str_repeat($maskChar, 2) . $space . str_repeat($maskChar, 3);
                }, $text) ?? $text;
            }

            if (!empty($autoOptions['initial_name'])) {
                // Инициалтай нэр (best-effort): О.Баасанцэрэн / О. Баасанцэрэн → О.Б**********
                $text = preg_replace_callback('/\\b([А-ЯӨҮ])\\.(\\s*)([А-ЯӨҮ])([а-яөү]{2,})\\b/u', function ($m) use ($maskChar) {
                    $initial = $m[1] ?? '';
                    $space = $m[2] ?? '';
                    $first = $m[3] ?? '';
                    $rest = $m[4] ?? '';
                    return $initial . '.' . $space . $first . str_repeat($maskChar, mb_strlen($rest));
                }, $text) ?? $text;
            }

            $node->nodeValue = $text;
        }

        return $doc->saveXML() ?: $xml;
    }
}

