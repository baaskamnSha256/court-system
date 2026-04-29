<?php

namespace App\Services\Notifications;

use App\Jobs\SendEmongoliaNotificationJob;
use App\Models\Hearing;
use Illuminate\Support\Facades\Log;
use Throwable;

class HearingNotificationService
{
    public function __construct(
        private readonly HearingNotificationPayloadBuilder $payloadBuilder
    ) {}

    public function send(Hearing $hearing, string $action = 'created'): void
    {
        $cfg = config('services.notification', []);
        if (! (bool) ($cfg['enabled'] ?? false)) {
            return;
        }

        $notifyUrl = (string) ($cfg['notify_url'] ?? '');
        $accessToken = (string) ($cfg['access_token'] ?? '');

        if ($notifyUrl === '' || $accessToken === '') {
            Log::warning('Notification API тохиргоо дутуу тул мэдэгдэл алгасав.', [
                'hearing_id' => $hearing->id,
                'action' => $action,
            ]);

            return;
        }

        try {
            $payload = $this->payloadBuilder->build($hearing, $action);
        } catch (Throwable $e) {
            Log::warning('Hearing notification payload бүрдүүлэхэд алдаа гарлаа.', [
                'hearing_id' => $hearing->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $connection = $cfg['queue_connection'] ?? null;
        $queue = $cfg['queue_name'] ?? null;

        foreach ($payload['recipients'] as $recipient) {
            $regnum = $recipient['regnum'] ?? null;
            if (empty($regnum)) {
                continue;
            }

            $recipientMessage = $this->buildRecipientMessage($payload['message'], $recipient);

            $job = SendEmongoliaNotificationJob::dispatch(
                $payload['title'],
                [
                    'Mail' => $recipientMessage,
                    'Messenger' => $recipientMessage,
                    'Notification' => $recipientMessage,
                ],
                $regnum,
                null,
                [
                    'hearing_id' => $hearing->id,
                    'action' => $action,
                    'role' => $recipient['role'] ?? null,
                    'name' => $recipient['name'] ?? null,
                ]
            );

            if (! empty($connection)) {
                $job->onConnection($connection);
            }

            if (! empty($queue)) {
                $job->onQueue($queue);
            }
        }
    }

    private function buildRecipientMessage(string $baseMessage, array $recipient): string
    {
        $role = $this->roleLabel((string) ($recipient['role'] ?? ''));
        $name = trim((string) ($recipient['name'] ?? ''));

        if ($role === '' && $name === '') {
            return $baseMessage;
        }

        if ($role !== '' && $name !== '') {
            return $role.' '.$name." та\n".$baseMessage;
        }

        return ($role !== '' ? $role : $name)." та\n".$baseMessage;
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'judge' => 'Шүүгч',
            'prosecutor' => 'Прокурор',
            'defendant_lawyer' => 'Шүүгдэгчийн өмгөөлөгч',
            'defendant' => 'Шүүгдэгч',
            'victim' => 'Хохирогч',
            'victim_legal_rep' => 'Хохирогчийн хууль ёсны төлөөлөгч',
            'victim_legal_rep_lawyer' => 'Хохирогчийн хууль ёсны төлөөлөгчийн өмгөөлөгч',
            'victim_lawyer' => 'Хохирогчийн өмгөөлөгч',
            'civil_plaintiff_lawyer' => 'Иргэний нэхэмжлэгчийн өмгөөлөгч',
            'civil_defendant_lawyer' => 'Иргэний хариуцагчийн өмгөөлөгч',
            'witness' => 'Гэрч',
            'civil_plaintiff' => 'Иргэний нэхэмжлэгч',
            'civil_defendant' => 'Иргэний хариуцагч',
            default => '',
        };
    }
}
