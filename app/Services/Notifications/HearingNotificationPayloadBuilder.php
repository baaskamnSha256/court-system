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
            "Шүүх хуралдааны зар:\n\nХэрэг № %s\nОгноо: %s\nЦаг: %s\nТанхим: %s\n\nТа хурлын цагаас 30 минутын өмнө ирнэ үү.\nМэдээлэл лавлагаа: %d",
            $caseNo,
            $date,
            $time,
            $courtroom,
            (int) $hearing->id
        );

        return [
            'title' => $title,
            'message' => $message,
            'recipients' => $this->recipientsResolver->resolve($hearing),
        ];
    }
}
