<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class NotificationTokenService
{
    public function getOneTimeToken(): string
    {
        $cfg = config('services.notification', []);
        $url = (string) ($cfg['token_url'] ?? '');
        $username = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 15);

        if ($url === '' || $username === '' || $password === '') {
            throw new RuntimeException('Notification token тохиргоо дутуу байна.');
        }

        $response = Http::timeout($timeout)
            ->asJson()
            ->post($url, [
                'username' => $username,
                'password' => $password,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Notification token авахад алдаа гарлаа.');
        }

        $token = (string) ($response->json('token') ?? '');
        if ($token === '') {
            $token = (string) ($response->json('data.token') ?? '');
        }
        if ($token === '') {
            throw new RuntimeException('Token хариу хоосон байна.');
        }

        return $token;
    }
}
