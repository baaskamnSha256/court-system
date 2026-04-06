<?php

namespace App\Services\Notifications;

use App\Models\Hearing;

class HearingNotificationPayloadBuilder
{
    public function __construct(private readonly RecipientsResolver $recipientsResolver) {}

    /**
     * @return array{
     *   title:string,
     *   message:string,
     *   recipients:array<int, array{role:string,name:string,phone:?string,regnum:?string,civil_id:?string}>
     * }
     */
    public function build(Hearing $hearing, string $action = 'created'): array
    {
        $title = $action === 'updated'
            ? 'Шүүх хурал шинэчлэгдлээ'
            : 'Шүүх хурал товлогдлоо';

        $date = $hearing->hearing_date?->format('Y-m-d') ?? optional($hearing->start_at)->format('Y-m-d') ?? '—';
        $time = optional($hearing->start_at)->format('H:i')
            ?? ($hearing->hour !== null && $hearing->minute !== null ? sprintf('%02d:%02d', $hearing->hour, $hearing->minute) : '—');
        $caseNo = $hearing->case_no ?: '—';
        $courtroom = $hearing->courtroom ?: '—';

        $message = sprintf(
            'Хэрэг № %s. Огноо: %s %s. Танхим: %s.',
            $caseNo,
            $date,
            $time,
            $courtroom
        );

        return [
            'title' => $title,
            'message' => $message,
            'recipients' => $this->recipientsResolver->resolve($hearing),
        ];
    }
}
