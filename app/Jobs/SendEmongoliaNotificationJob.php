<?php

namespace App\Jobs;

use App\Services\Notifications\NotificationLogService;
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

    public function handle(NotificationTokenService $tokenService, ?NotificationLogService $notificationLogService = null): void
    {
        $notificationLogService ??= app(NotificationLogService::class);

        $cfg = config('services.notification', []);
        if (! (bool) ($cfg['enabled'] ?? false)) {
            return;
        }

        $notifyUrl = (string) ($cfg['notify_url'] ?? '');
        $accessToken = (string) ($cfg['access_token'] ?? '');
        $timeout = (int) ($cfg['timeout'] ?? 15);

        if ($notifyUrl === '' || $accessToken === '') {
            Log::warning('Emongolia notification тохиргоо дутуу тул илгээгдсэнгүй.', $this->context);
            $notificationLogService->store($this->buildLogData(
                deliveryStatus: 'skipped_config',
                delivered: false,
                apiMessage: 'Missing notification configuration'
            ));

            return;
        }

        if (empty($this->regnum) && empty($this->civilId)) {
            Log::info('Emongolia notification: regnum/civilId хоёулаа хоосон тул алгаслаа.', $this->context);
            $notificationLogService->store($this->buildLogData(
                deliveryStatus: 'skipped_missing_recipient',
                delivered: false,
                apiMessage: 'Both regnum and civilId are empty'
            ));

            return;
        }

        try {
            $oneTimeToken = $tokenService->getOneTimeToken();
        } catch (Throwable $e) {
            Log::warning('Emongolia notification: token авч чадсангүй.', [
                'error' => $e->getMessage(),
                'context' => $this->context,
            ]);
            $notificationLogService->store($this->buildLogData(
                deliveryStatus: 'token_error',
                delivered: false,
                apiMessage: $e->getMessage()
            ));

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
            $notificationLogService->store($this->buildLogData(
                payload: $payload,
                deliveryStatus: 'request_error',
                delivered: false,
                apiMessage: $e->getMessage()
            ));

            throw $e;
        }

        $body = (string) $response->body();
        $truncated = mb_strlen($body) > 500 ? mb_substr($body, 0, 500).'…' : $body;

        $apiStatus = (int) ($response->json('status') ?? 0);
        $apiMessage = (string) ($response->json('Message') ?? $response->json('message') ?? '');
        $requestId = (string) ($response->json('RequestId')
            ?? $response->json('requestId')
            ?? $response->json('requestid')
            ?? '');

        $logContext = [
            'channel' => 'emongolia_notification',
            'delivery_status' => 'unknown',
            'http_status' => $response->status(),
            'api_status' => $apiStatus,
            'api_message' => $apiMessage,
            'request_id' => $requestId,
            'regnum' => $this->regnum,
            'civilId' => $this->civilId,
            'recipient_role' => $this->context['role'] ?? null,
            'recipient_name' => $this->context['name'] ?? null,
            'title' => $this->title,
            'payload' => [
                'regnum' => $payload['regnum'] ?? null,
                'civilId' => $payload['civilId'] ?? null,
                'body_channels' => array_keys((array) ($payload['body'] ?? [])),
            ],
            'context' => $this->context,
        ];

        $hasApiStatus = $response->json('status') !== null;
        $isSuccess = $response->successful() && (! $hasApiStatus || $apiStatus === 200);

        if (! $isSuccess) {
            $statusCode = (int) ($response->json('status') ?? 0);
            if ($statusCode === 701) {
                $context = $logContext + [
                    'delivery_status' => 'not_registered',
                    'delivered' => false,
                    'response_body' => $truncated,
                ];
                Log::warning('Emongolia notification delivery result.', $context);
                $notificationLogService->store($this->buildLogData(
                    payload: $payload,
                    deliveryStatus: 'not_registered',
                    delivered: false,
                    httpStatus: $response->status(),
                    apiStatus: $apiStatus,
                    apiMessage: $apiMessage,
                    requestId: $requestId,
                    responseBody: $truncated
                ));

                return;
            }

            $context = $logContext + [
                'delivery_status' => 'failed',
                'delivered' => false,
                'response_body' => $truncated,
            ];
            Log::warning('Emongolia notification delivery result.', $context);
            $notificationLogService->store($this->buildLogData(
                payload: $payload,
                deliveryStatus: 'failed',
                delivered: false,
                httpStatus: $response->status(),
                apiStatus: $apiStatus,
                apiMessage: $apiMessage,
                requestId: $requestId,
                responseBody: $truncated
            ));

            if (in_array($response->status(), [408, 429, 500, 502, 503, 504], true)) {
                $this->release($this->backoff);
            }

            return;
        }

        Log::info('Emongolia notification delivery result.', $logContext + [
            'delivery_status' => 'delivered',
            'delivered' => true,
            'response_body' => $truncated,
        ]);
        $notificationLogService->store($this->buildLogData(
            payload: $payload,
            deliveryStatus: 'delivered',
            delivered: true,
            httpStatus: $response->status(),
            apiStatus: $apiStatus,
            apiMessage: $apiMessage,
            requestId: $requestId,
            responseBody: $truncated
        ));
    }

    public function failed(Throwable $e): void
    {
        Log::error('Emongolia notification job бүрэн бүтэлгүйтлээ.', [
            'error' => $e->getMessage(),
            'regnum' => $this->regnum,
            'civilId' => $this->civilId,
            'context' => $this->context,
        ]);

        app(NotificationLogService::class)->store($this->buildLogData(
            deliveryStatus: 'job_failed',
            delivered: false,
            apiMessage: $e->getMessage()
        ));
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{
     *   hearing_id:int|null,
     *   action:string|null,
     *   recipient_role:string|null,
     *   recipient_name:string|null,
     *   regnum:string|null,
     *   civil_id:string|null,
     *   title:string,
     *   delivery_status:string,
     *   delivered:bool,
     *   http_status:int|null,
     *   api_status:int|null,
     *   api_message:string|null,
     *   request_id:string|null,
     *   payload:array<string, mixed>|null,
     *   context:array<string, mixed>,
     *   response_body:string|null
     * }
     */
    private function buildLogData(
        ?array $payload = null,
        string $deliveryStatus = 'unknown',
        bool $delivered = false,
        ?int $httpStatus = null,
        ?int $apiStatus = null,
        ?string $apiMessage = null,
        ?string $requestId = null,
        ?string $responseBody = null
    ): array {
        return [
            'hearing_id' => isset($this->context['hearing_id']) ? (int) $this->context['hearing_id'] : null,
            'action' => isset($this->context['action']) ? (string) $this->context['action'] : null,
            'recipient_role' => isset($this->context['role']) ? (string) $this->context['role'] : null,
            'recipient_name' => isset($this->context['name']) ? (string) $this->context['name'] : null,
            'regnum' => $this->regnum,
            'civil_id' => $this->civilId,
            'title' => $this->title,
            'delivery_status' => $deliveryStatus,
            'delivered' => $delivered,
            'http_status' => $httpStatus,
            'api_status' => $apiStatus,
            'api_message' => $apiMessage,
            'request_id' => $requestId,
            'payload' => $payload,
            'context' => $this->context,
            'response_body' => $responseBody,
        ];
    }
}
