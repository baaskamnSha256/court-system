<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'hearing_id',
        'action',
        'recipient_role',
        'recipient_name',
        'regnum',
        'civil_id',
        'title',
        'delivery_status',
        'delivered',
        'http_status',
        'api_status',
        'api_message',
        'request_id',
        'payload',
        'context',
        'response_body',
        'sent_at',
    ];

    protected $casts = [
        'delivered' => 'bool',
        'http_status' => 'integer',
        'api_status' => 'integer',
        'payload' => 'array',
        'context' => 'array',
        'sent_at' => 'datetime',
    ];

    public function hearing(): BelongsTo
    {
        return $this->belongsTo(Hearing::class);
    }
}
