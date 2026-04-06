<?php

namespace App\Services\Notifications;

use App\Models\Hearing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class HearingNotificationService
{
    public function __construct(
        private readonly HearingNotificationPayloadBuilder $payloadBuilder,
        private readonly NotificationTokenService $tokenService
    ) {}

    public function send(Hearing $hearing, string $action = 'created'): void
    {
        $cfg = config('services.notification', []);
        if (! (bool) ($cfg['enabled'] ?? false)) {
            return;
        }

        $notifyUrl = (string) ($cfg['notify_url'] ?? '');
        $accessToken = (string) ($cfg['access_token'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 15);

        if ($notifyUrl === '' || $accessToken === '') {
            Log::warning('Notification API тохиргоо дутуу тул мэдэгдэл алгасав.');

            return;
        }

        try {
            $oneTimeToken = $this->tokenService->getOneTimeToken();
            $payload = $this->payloadBuilder->build($hearing, $action);

            foreach ($payload['recipients'] as $recipient) {
                if (empty($recipient['regnum']) || empty($recipient['civil_id'])) {
                    continue;
                }

                $body = [
                    'title' => $payload['title'],
                    'body' => [
                        'Mail' => $payload['message'],
                        'Messenger' => $payload['message'],
                        'Notification' => $payload['message'],
                    ],
                    'regnum' => $recipient['regnum'],
                    'civilId' => $recipient['civil_id'],
                ];

                Http::timeout($timeout)
                    ->asJson()
                    ->withHeaders([
                        'Accesstoken' => $accessToken,
                        'Authorization' => 'Bearer '.$oneTimeToken,
                    ])
                    ->post($notifyUrl, $body);
            }
        } catch (Throwable $e) {
            Log::warning('Hearing notification илгээхэд алдаа гарлаа.', [
                'hearing_id' => $hearing->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
