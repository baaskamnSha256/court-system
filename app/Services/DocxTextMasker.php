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
     * @param  string  $outputPath  Output .docx file path (will be overwritten)
     * @param  array<int,string>  $phrases  Exact phrases to mask (case-insensitive)
     * @param  array<string,bool>  $autoOptions
     * @param  string  $maskChar  Character used to mask (repeated)
     * @param  array<int,string>  $excludePhrases  Phrases that must never be masked (e.g. judges, lawyers, prosecutors)
     */
    public function mask(string $inputPath, string $outputPath, array $phrases, array $autoOptions = [], string $maskChar = '*', array $excludePhrases = []): void
    {
        if (! is_file($inputPath)) {
            throw new RuntimeException('Input file not found.');
        }
        if (! copy($inputPath, $outputPath)) {
            throw new RuntimeException('Failed to prepare output file.');
        }

        $zip = new ZipArchive;
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
            if ($xml === false) {
                continue;
            }

            $updated = $this->maskWordXml($xml, $phrases, $autoOptions, $maskChar, $excludePhrases);
            $zip->addFromString($path, $updated);
        }

        $zip->close();
    }

    /**
     * Best-effort: extract visible plain text from a .docx (document.xml only).
     */
    public function extractPlainText(string $docxPath): string
    {
        if (! is_file($docxPath)) {
            throw new RuntimeException('Docx file not found.');
        }

        $zip = new ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('Unable to open docx as zip.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $doc = new DOMDocument;
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        if (! @$doc->loadXML($xml)) {
            return '';
        }

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Join paragraphs with newline; join runs within paragraph with empty string
        $paras = $xp->query('//w:p');
        if (! $paras) {
            return '';
        }

        $out = [];
        foreach ($paras as $p) {
            $texts = $xp->query('.//w:t', $p);
            if (! $texts) {
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

    private function maskWordXml(string $xml, array $phrases, array $autoOptions, string $maskChar, array $excludePhrases): string
    {
        $doc = new DOMDocument;
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;

        // Suppress XML warnings from Word's namespaces.
        if (! @$doc->loadXML($xml)) {
            return $xml;
        }

        $xp = new DOMXPath($doc);
        $xp->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = $xp->query('//w:p');
        if (! $paragraphs) {
            return $doc->saveXML() ?: $xml;
        }

        $phrases = $this->normalizePhrases($phrases);

        foreach ($paragraphs as $paragraph) {
            $nodes = $xp->query('.//w:t', $paragraph);
            if (! $nodes || $nodes->length === 0) {
                continue;
            }

            $originalParts = [];
            $fullText = '';
            foreach ($nodes as $node) {
                $part = $node->nodeValue ?? '';
                $originalParts[] = $part;
                $fullText .= $part;
            }

            if ($fullText === '') {
                continue;
            }

            $maskedText = $this->applyMasks($fullText, $phrases, $autoOptions, $maskChar, $excludePhrases);
            if ($maskedText === $fullText) {
                continue;
            }

            $cursor = 0;
            $partCount = count($originalParts);
            foreach ($nodes as $index => $node) {
                $partLength = mb_strlen($originalParts[$index]);

                if ($index === $partCount - 1) {
                    $node->nodeValue = mb_substr($maskedText, $cursor);

                    continue;
                }

                $node->nodeValue = mb_substr($maskedText, $cursor, $partLength);
                $cursor += $partLength;
            }
        }

        return $doc->saveXML() ?: $xml;
    }

    /**
     * @param  array<int, string>  $phrases
     * @return array<int, string>
     */
    private function normalizePhrases(array $phrases): array
    {
        $phrases = array_values(array_filter(array_map('trim', $phrases), fn ($value) => $value !== ''));
        $quoteChars = "\"'“”«»";
        $expandedPhrases = [];

        foreach ($phrases as $phrase) {
            $expandedPhrases[] = $phrase;
            $unquoted = trim($phrase, $quoteChars." \t\n\r\0\x0B");
            if ($unquoted !== '' && $unquoted !== $phrase) {
                $expandedPhrases[] = $unquoted;
            }
        }

        return array_values(array_unique($expandedPhrases));
    }

    /**
     * @param  array<int, string>  $phrases
     * @param  array<string, bool>  $autoOptions
     * @param  array<int, string>  $excludePhrases
     */
    private function applyMasks(string $text, array $phrases, array $autoOptions, string $maskChar, array $excludePhrases = []): string
    {
        $original = $text;
        $quoteChars = "\"'“”«»";
        $phrases = $this->sortPhrasesLongestFirst($phrases);

        foreach ($phrases as $phrase) {
            if ($phrase === '') {
                continue;
            }

            $quotedPhrase = preg_quote($phrase, '/');
            $text = preg_replace_callback('/'.$quotedPhrase.'/iu', function ($matches) use ($maskChar) {
                $value = $matches[0] ?? '';

                return str_repeat($maskChar, mb_strlen($value));
            }, $text) ?? $text;

            $text = preg_replace_callback('/(['.preg_quote($quoteChars, '/').'])\\s*('.$quotedPhrase.')\\s*\\1/iu', function ($matches) use ($maskChar) {
                $quote = $matches[1] ?? '"';
                $inner = $matches[2] ?? '';

                return $quote.str_repeat($maskChar, mb_strlen($inner)).$quote;
            }, $text) ?? $text;
        }

        if (! empty($autoOptions['phone'])) {
            $text = preg_replace('/(?:\\+?976\\s*)?(\\b\\d{8}\\b)/u', str_repeat($maskChar, 8), $text) ?? $text;
        }
        if (! empty($autoOptions['register'])) {
            $text = preg_replace('/\\b[А-ЯӨҮ]{2}\\d{8}\\b/u', str_repeat($maskChar, 10), $text) ?? $text;
        }
        if (! empty($autoOptions['plate'])) {
            $text = preg_replace_callback('/\\b(?:(\\d{4})(\\s?)([A-ZА-ЯӨҮ]{3})|(\\d{2})-(\\d{2})(\\s?)([A-ZА-ЯӨҮ]{3}))\\b/u', function ($matches) use ($maskChar) {
                if (! empty($matches[1])) {
                    $space = $matches[2] ?? '';

                    return str_repeat($maskChar, 4).$space.str_repeat($maskChar, 3);
                }

                $space = $matches[6] ?? '';

                return str_repeat($maskChar, 2).'-'.str_repeat($maskChar, 2).$space.str_repeat($maskChar, 3);
            }, $text) ?? $text;
        }

        if (! empty($autoOptions['initial_name'])) {
            $text = preg_replace_callback('/\\b([А-ЯӨҮ])\\.(\\s*)([А-ЯӨҮ])([а-яөү]{2,})\\b/u', function ($matches) use ($maskChar) {
                $initial = $matches[1] ?? '';
                $space = $matches[2] ?? '';
                $first = $matches[3] ?? '';
                $rest = $matches[4] ?? '';

                return $initial.'.'.$space.$first.str_repeat($maskChar, mb_strlen($rest));
            }, $text) ?? $text;
        }

        if (! empty($autoOptions['ovogt_name'])) {
            $text = preg_replace_callback(
                '/\\b([А-ЯӨҮ][а-яөү-]+)\\s+овогт\\s+([А-ЯӨҮ][а-яөү]+?)(гийн|ийн|ын)\\s+([А-ЯӨҮ][а-яөү]+)\\b/iu',
                function (array $matches) use ($maskChar): string {
                    $ovogLabel = $matches[1] ?? '';
                    $familyStem = $matches[2] ?? '';
                    $familySuffix = $matches[3] ?? '';
                    $givenName = $matches[4] ?? '';

                    return $ovogLabel.' овогт '
                        .$this->maskNameStem($familyStem, $maskChar)
                        .$familySuffix.' '
                        .$this->maskNameStem($givenName, $maskChar);
                },
                $text
            ) ?? $text;
        }

        return $this->restoreExcludedPhrases($original, $text, $excludePhrases);
    }

    /**
     * @param  array<int, string>  $phrases
     * @return array<int, string>
     */
    private function sortPhrasesLongestFirst(array $phrases): array
    {
        usort($phrases, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $phrases;
    }

    /**
     * @param  array<int, string>  $excludePhrases
     */
    private function restoreExcludedPhrases(string $original, string $masked, array $excludePhrases): string
    {
        $excludePhrases = $this->sortPhrasesLongestFirst($this->normalizePhrases($excludePhrases));

        foreach ($excludePhrases as $phrase) {
            $masked = $this->restorePhraseOccurrences($original, $masked, $phrase);
        }

        return $masked;
    }

    private function maskNameStem(string $stem, string $maskChar): string
    {
        if ($stem === '') {
            return '';
        }

        if (mb_strlen($stem) <= 1) {
            return str_repeat($maskChar, mb_strlen($stem));
        }

        return mb_substr($stem, 0, 1).str_repeat($maskChar, mb_strlen($stem) - 1);
    }

    private function restorePhraseOccurrences(string $original, string $masked, string $phrase): string
    {
        if ($phrase === '') {
            return $masked;
        }

        $searchOffset = 0;
        while (($position = mb_stripos($original, $phrase, $searchOffset)) !== false) {
            $length = mb_strlen($phrase);
            $masked = mb_substr($masked, 0, $position)
                .mb_substr($original, $position, $length)
                .mb_substr($masked, $position + $length);
            $searchOffset = $position + $length;
        }

        return $masked;
    }
}
