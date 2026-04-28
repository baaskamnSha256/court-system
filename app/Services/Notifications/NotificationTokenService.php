<?php

namespace App\Services\Notifications;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class NotificationTokenService
{
    /**
     * notification.mn /api/v1/external/token -аас нэг удаагийн токен авна.
     */
    public function getOneTimeToken(): string
    {
        $cfg = config('services.notification', []);
        $url = (string) ($cfg['token_url'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 15);

        if ($url === '' || $username === '' || $password === '') {
            throw new RuntimeException('Notification token тохиргоо дутуу байна (token_url/username/password).');
        }

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'username' => $username,
                    'password' => $password,
                ]);
        } catch (Throwable $e) {
            Log::warning('Notification token хүсэлт илгээхэд алдаа гарлаа.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Notification token хүсэлт илгээж чадсангүй: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('Notification token авах хариу амжилтгүй.', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $this->truncateBody($response),
            ]);

            throw new RuntimeException(sprintf(
                'Notification token авахад алдаа гарлаа (HTTP %d).',
                $response->status()
            ));
        }

        $token = (string) ($response->json('token')
            ?? $response->json('data.token')
            ?? $response->json('result.token')
            ?? '');

        if ($token === '') {
            Log::warning('Notification token хариу хоосон байна.', [
                'body' => $this->truncateBody($response),
            ]);

            throw new RuntimeException('Token хариу хоосон байна.');
        }

        return $token;
    }

    private function truncateBody(Response $response, int $max = 1000): string
    {
        $body = (string) $response->body();

        return mb_strlen($body) > $max ? mb_substr($body, 0, $max).'…' : $body;
    }
}
