<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class NotificationFailedRegnumsCommand extends Command
{
    protected $signature = 'notification:failed-regnums
        {--log= : Лог файлын зам (default: storage/logs/laravel.log)}
        {--limit=5000 : Сүүлээс унших мөрийн дээд хязгаар}
        {--hearing-id= : Тухайн hearing_id-аар шүүх}';

    protected $description = 'Emongolia 701 (Бүртгэлгүй хэрэглэгч) болсон регистрүүдийн жагсаалтыг гаргана.';

    public function handle(): int
    {
        $logPath = (string) ($this->option('log') ?: storage_path('logs/laravel.log'));
        $limit = max(1, (int) $this->option('limit'));
        $hearingId = $this->option('hearing-id');

        if (! File::exists($logPath)) {
            $this->components->error("Лог файл олдсонгүй: {$logPath}");

            return self::FAILURE;
        }

        $lines = file($logPath, FILE_IGNORE_NEW_LINES);
        if (! is_array($lines)) {
            $this->components->error('Лог файлыг уншиж чадсангүй.');

            return self::FAILURE;
        }

        $rows = [];
        $slice = array_slice($lines, -$limit);
        foreach ($slice as $line) {
            if (! str_contains($line, 'Emongolia notification')) {
                continue;
            }
            if (! str_contains($line, '"api_status":701')) {
                continue;
            }

            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) {
                continue;
            }

            $json = substr($line, $jsonStart);
            $decoded = json_decode($json, true);
            if (! is_array($decoded)) {
                continue;
            }

            $context = (array) ($decoded['context'] ?? []);
            if ($hearingId !== null && (string) ($context['hearing_id'] ?? '') !== (string) $hearingId) {
                continue;
            }

            $responseBody = (string) ($decoded['response_body'] ?? '');
            $providerRequestId = '';
            if ($responseBody !== '') {
                $providerDecoded = json_decode($responseBody, true);
                if (is_array($providerDecoded)) {
                    $providerRequestId = (string) ($providerDecoded['requestid'] ?? $providerDecoded['requestId'] ?? '');
                }
            }

            $rows[] = [
                'regnum' => (string) ($decoded['regnum'] ?? ''),
                'role' => (string) ($context['role'] ?? ''),
                'name' => (string) ($context['name'] ?? ''),
                'hearing_id' => (string) ($context['hearing_id'] ?? ''),
                'requestid' => $providerRequestId,
            ];
        }

        if ($rows === []) {
            $this->components->info('701 алдаа олдсонгүй.');

            return self::SUCCESS;
        }

        $grouped = collect($rows)
            ->groupBy(fn (array $row) => $row['regnum'].'|'.$row['role'].'|'.$row['name'].'|'.$row['hearing_id'])
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'regnum' => $first['regnum'],
                    'role' => $first['role'],
                    'name' => $first['name'],
                    'hearing_id' => $first['hearing_id'],
                    'count' => (string) $items->count(),
                    'last_requestid' => (string) ($items->last()['requestid'] ?? ''),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->all();

        $this->components->info('701 (Бүртгэлгүй хэрэглэгч) гарсан регистрүүд:');
        $this->table(
            ['regnum', 'role', 'name', 'hearing_id', 'count', 'last_requestid'],
            $grouped
        );

        return self::SUCCESS;
    }
}

