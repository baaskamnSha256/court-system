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
            ? 'Шүүх хуралдааны зар шинэчлэгдлээ'
            : 'Шүүх хуралдааны зар товлогдлоо';

        $date = $hearing->hearing_date?->format('Y-m-d') ?? optional($hearing->start_at)->format('Y-m-d') ?? '—';
        $caseNo = $hearing->case_no ?: '—';
        $courtroom = $hearing->courtroom ?: '—';
        $hour = $hearing->hour ?? optional($hearing->start_at)->format('H') ?? '—';
        $minute = $hearing->minute ?? optional($hearing->start_at)->format('i') ?? '—';

        $message = sprintf(
            "%s дугаартай хэргийн {{role}} та {{role_id}} %s-нд Баянзүрх, Сүхбаатар, Чингэлтэй дүүргийн эрүүгийн хэргийн анхан шатны Тойргийн шүүхийн /Сансар КТМС-ийн ард/ байранд %s танхимд %s цаг %s минутанд ирж шүүх хуралдаанд оролцоно уу.\nТа хурлын цагаас 30 минутын өмнө ирсэн байхыг анхаарна уу.\nМэдээлэл лавлагаа: 11-458240",
            $caseNo,
            $date,
            $courtroom,
            $hour,
            $minute
        );

        return [
            'title' => $title,
            'message' => $message,
            'recipients' => $this->recipientsResolver->resolve($hearing),
        ];
    }
}
