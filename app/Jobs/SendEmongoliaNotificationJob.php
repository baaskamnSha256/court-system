<?php

namespace App\Jobs;

use App\Services\Notifications\NotificationTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Emongolia (notification.mn) руу нэг мэдэгдэл илгээнэ.
 *
 * Нэг удаагийн токенийг job-ын дотор шинээр авдаг тул retry үед
 * хуучирсан токенээр дахин илгээгдэхгүй.
 */
class SendEmongoliaNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 30;

    /**
     * @param  array{Mail?:string,Messenger?:string,Notification?:string}|string  $body
     */
    public function __construct(
        public readonly string $title,
        public readonly array|string $body,
        public readonly ?string $regnum,
        public readonly ?string $civilId,
        public readonly array $context = []
    ) {}

    public function handle(NotificationTokenService $tokenService): void
    {
        $cfg = config('services.notification', []);
        if (! (bool) ($cfg['enabled'] ?? false)) {
            return;
        }

        $notifyUrl = (string) ($cfg['notify_url'] ?? '');
        $accessToken = (string) ($cfg['access_token'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 15);

        if ($notifyUrl === '' || $accessToken === '') {
            Log::warning('Emongolia notification тохиргоо дутуу тул илгээгдсэнгүй.', $this->context);

            return;
        }

        if (empty($this->regnum) && empty($this->civilId)) {
            Log::info('Emongolia notification: regnum/civilId хоёулаа хоосон тул алгаслаа.', $this->context);

            return;
        }

        try {
            $oneTimeToken = $tokenService->getOneTimeToken();
        } catch (Throwable $e) {
            Log::warning('Emongolia notification: token авч чадсангүй.', [
                'error' => $e->getMessage(),
                'context' => $this->context,
            ]);

            $this->release($this->backoff);

            return;
        }

        $payload = [
            'title' => $this->title,
            'body' => is_array($this->body)
                ? $this->body
                : [
                    'Mail' => $this->body,
                    'Messenger' => $this->body,
                    'Notification' => $this->body,
                ],
        ];

        if (! empty($this->regnum)) {
            $payload['regnum'] = $this->regnum;
        }

        if (! empty($this->civilId)) {
            $payload['civilId'] = $this->civilId;
        }

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Accesstoken' => $accessToken,
                    'Authorization' => 'Bearer '.$oneTimeToken,
                ])
                ->post($notifyUrl, $payload);
        } catch (Throwable $e) {
            Log::warning('Emongolia notification: хүсэлт илгээхэд алдаа гарлаа.', [
                'error' => $e->getMessage(),
                'context' => $this->context,
            ]);

            throw $e;
        }

        $body = (string) $response->body();
        $truncated = mb_strlen($body) > 500 ? mb_substr($body, 0, 500).'…' : $body;

        $apiStatus = (int) ($response->json('status') ?? 0);
        $apiMessage = (string) ($response->json('Message') ?? $response->json('message') ?? '');
        $requestId = (string) ($response->json('RequestId') ?? $response->json('requestId') ?? '');

        $logContext = [
            'http_status' => $response->status(),
            'api_status' => $apiStatus,
            'api_message' => $apiMessage,
            'request_id' => $requestId,
            'regnum' => $this->regnum,
            'civilId' => $this->civilId,
            'context' => $this->context,
        ];

        $hasApiStatus = $response->json('status') !== null;
        $isSuccess = $response->successful() && (! $hasApiStatus || $apiStatus === 200);

        if (! $isSuccess) {
            Log::warning('Emongolia notification: амжилтгүй хариу.', $logContext + ['body' => $truncated]);

            if (in_array($response->status(), [408, 429, 500, 502, 503, 504], true)) {
                $this->release($this->backoff);
            }

            return;
        }

        Log::info('Emongolia notification илгээгдлээ.', $logContext);
    }

    public function failed(Throwable $e): void
    {
        Log::error('Emongolia notification job бүрэн бүтэлгүйтлээ.', [
            'error' => $e->getMessage(),
            'regnum' => $this->regnum,
            'civilId' => $this->civilId,
            'context' => $this->context,
        ]);
    }
}
