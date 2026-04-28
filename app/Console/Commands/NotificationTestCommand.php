<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationTokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class NotificationTestCommand extends Command
{
    protected $signature = 'notification:test
        {--regnum= : Хүлээн авагчийн регистр}
        {--civil-id= : Хүлээн авагчийн иргэний ID (байхгүй бол regnum)}
        {--title=Туршилтын мэдэгдэл : Гарчиг}
        {--message=Энэ бол Emongolia API туршилтын мэдэгдэл юм. : Текст}
        {--token-only : Зөвхөн токен авах хэсгийг туршина}';

    protected $description = 'Emongolia (notification.mn) API-тай холболт, токен, мэдэгдэл илгээлтийг туршина.';

    public function handle(NotificationTokenService $tokenService): int
    {
        $cfg = config('services.notification', []);
        $tokenUrl = (string) ($cfg['token_url'] ?? '');
        $notifyUrl = (string) ($cfg['notify_url'] ?? '');
        $accessToken = (string) ($cfg['access_token'] ?? '');

        $this->components->info('Emongolia тохиргоо');
        $this->components->twoColumnDetail('Идэвхтэй эсэх', (bool) ($cfg['enabled'] ?? false) ? 'true' : 'false');
        $this->components->twoColumnDetail('token_url', $tokenUrl ?: '(хоосон)');
        $this->components->twoColumnDetail('notify_url', $notifyUrl ?: '(хоосон)');
        $this->components->twoColumnDetail('access_token', $accessToken !== '' ? '('.strlen($accessToken).' тэмдэгт)' : '(хоосон)');
        $this->components->twoColumnDetail('username', (string) ($cfg['username'] ?? '') ?: '(хоосон)');

        $this->newLine();
        $this->components->task('Нэг удаагийн токен авах', function () use ($tokenService, &$oneTimeToken): bool {
            try {
                $oneTimeToken = $tokenService->getOneTimeToken();

                return $oneTimeToken !== '';
            } catch (Throwable $e) {
                $this->newLine();
                $this->components->error($e->getMessage());
                $oneTimeToken = null;

                return false;
            }
        });

        if (empty($oneTimeToken)) {
            return self::FAILURE;
        }

        $this->components->twoColumnDetail('Токен уртааш', (string) strlen($oneTimeToken).' тэмдэгт');
        $this->components->twoColumnDetail('Токен (эхний 16)', substr($oneTimeToken, 0, 16).'…');

        if ($this->option('token-only')) {
            return self::SUCCESS;
        }

        $regnum = (string) ($this->option('regnum') ?? '');
        $civilId = (string) ($this->option('civil-id') ?? $regnum);

        if ($regnum === '' && $civilId === '') {
            $this->components->warn('Мэдэгдэл илгээхийн тулд --regnum эсвэл --civil-id өгнө үү. (--token-only туршина уу)');

            return self::SUCCESS;
        }

        if ($notifyUrl === '' || $accessToken === '') {
            $this->components->error('notify_url эсвэл access_token тохируулагдаагүй байна.');

            return self::FAILURE;
        }

        $title = (string) $this->option('title');
        $message = (string) $this->option('message');

        $payload = [
            'title' => $title,
            'body' => [
                'Mail' => $message,
                'Messenger' => $message,
                'Notification' => $message,
            ],
        ];
        if ($regnum !== '') {
            $payload['regnum'] = $regnum;
        }
        if ($civilId !== '') {
            $payload['civilId'] = $civilId;
        }

        $this->newLine();
        $this->components->info('Мэдэгдэл илгээж байна…');

        try {
            $response = Http::timeout((int) ($cfg['timeout'] ?? 15))
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Accesstoken' => $accessToken,
                    'Authorization' => 'Bearer '.$oneTimeToken,
                ])
                ->post($notifyUrl, $payload);
        } catch (Throwable $e) {
            $this->components->error('HTTP алдаа: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->components->twoColumnDetail('HTTP статус', (string) $response->status());

        $apiStatus = $response->json('status');
        $apiMessage = (string) ($response->json('Message') ?? $response->json('message') ?? '');
        $requestId = (string) ($response->json('RequestId') ?? $response->json('requestId') ?? '');

        if ($apiStatus !== null) {
            $this->components->twoColumnDetail('API status', (string) $apiStatus);
        }
        if ($apiMessage !== '') {
            $this->components->twoColumnDetail('Message', $apiMessage);
        }
        if ($requestId !== '') {
            $this->components->twoColumnDetail('RequestId', $requestId);
        }

        $body = (string) $response->body();
        $this->newLine();
        $this->line($body);

        $hasApiStatus = $apiStatus !== null;
        $ok = $response->successful() && (! $hasApiStatus || (int) $apiStatus === 200);

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
