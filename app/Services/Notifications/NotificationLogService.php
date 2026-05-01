<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use Carbon\Carbon;

class NotificationLogService
{
    /**
     * @param  array{
     *     hearing_id?:int|null,
     *     action?:string|null,
     *     recipient_role?:string|null,
     *     recipient_name?:string|null,
     *     regnum?:string|null,
     *     civil_id?:string|null,
     *     title:string,
     *     delivery_status:string,
     *     delivered:bool,
     *     http_status?:int|null,
     *     api_status?:int|null,
     *     api_message?:string|null,
     *     request_id?:string|null,
     *     payload?:array<string, mixed>|null,
     *     context?:array<string, mixed>|null,
     *     response_body?:string|null,
     *     sent_at?:string|\DateTimeInterface|null
     * } $data
     */
    public function store(array $data): NotificationLog
    {
        return NotificationLog::query()->create([
            'hearing_id' => $data['hearing_id'] ?? null,
            'action' => $data['action'] ?? null,
            'recipient_role' => $data['recipient_role'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'regnum' => $data['regnum'] ?? null,
            'civil_id' => $data['civil_id'] ?? null,
            'title' => $data['title'],
            'delivery_status' => $data['delivery_status'],
            'delivered' => (bool) ($data['delivered'] ?? false),
            'http_status' => $data['http_status'] ?? null,
            'api_status' => $data['api_status'] ?? null,
            'api_message' => $data['api_message'] ?? null,
            'request_id' => $data['request_id'] ?? null,
            'payload' => $data['payload'] ?? null,
            'context' => $data['context'] ?? null,
            'response_body' => $data['response_body'] ?? null,
            'sent_at' => isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : now(),
        ]);
    }
}
