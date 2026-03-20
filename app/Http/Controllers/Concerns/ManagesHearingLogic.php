<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Hearing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ManagesHearingLogic
{
    protected function allowedCourtrooms(): array
    {
        return ['A', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж'];
    }

    protected function allowedMinutes(): array
    {
        return [0, 10, 20, 30, 40, 50];
    }

    protected function allowedPreventiveMeasures(): array
    {
        return [
            'хувийн баталгаа гаргах',
            'тодорхой үйл ажиллагаа явуулах, албан үүргээ биелүүлэхийг түдгэлзүүлэх',
            'хязгаарлалт тогтоох',
            'барьцаа авах',
            'цагдан хорих',
            'цэргийн ангийн удирдлагад хянан харгалзуулах',
        ];
    }

    protected function allowedMatterCategories(): array
    {
        return [
            'Эрүүгийн хэрэг',
            'Эрүүгийн хариуцлага',
            'Урьдчилсан хэлэлцүүлэг',
            'Иргэний хэрэг',
            'Захиргааны хэрэг',
            'Хөдөлмөрийн хэрэг',
            'Татан буулгах хэрэг',
            'Бусад',
        ];
    }

    protected function allowedSessionOpenness(): array
    {
        return ['Нээлттэй', 'Хаалттай'];
    }

    /** Хурлын төлөв — default: Хэвийн */
    protected function allowedHearingStates(): array
    {
        return [
            'Хэвийн',
            'Урьдчилсан хэлэлцүүлэг',
            'Эрүүгийн хариуцлага',
            'Хаалттай',
            'Гэм буруугүй',
        ];
    }

    protected function normalizeJudgeIds(Request $request): array
    {
        $ids = [
            $request->input('presiding_judge_id'),
            $request->input('member_judge_1_id'),
            $request->input('member_judge_2_id'),
        ];
        $ids = array_filter($ids);
        $ids = array_map('intval', $ids);
        return array_values(array_unique($ids));
    }

    protected function computeDurationMinutes(array $judgeIds): int
    {
        return count($judgeIds) >= 3 ? 60 : 30;
    }

    protected function buildStartEnd(string $date, int $hour, int $minute, int $durationMinutes): array
    {
        $start = Carbon::parse($date)->setTime($hour, $minute, 0);
        $end = (clone $start)->addMinutes($durationMinutes);
        return [$start, $end];
    }

    protected function assertOneOrThreeJudges(array $judgeIds): void
    {
        if (count($judgeIds) === 2) {
            throw ValidationException::withMessages([
                'member_judge_1_id' => '2 шүүгчтэй хурал зөвшөөрөгдөхгүй. 1 эсвэл 3 шүүгч сонгоно.',
            ]);
        }
    }

    protected function assertNoConflict(
        Carbon $start,
        Carbon $end,
        string $courtroom,
        array $judgeIds,
        array $lawyerNames = [],
        ?int $ignoreHearingId = null,
        array $prosecutorIds = []
    ): void {
        $q = Hearing::query()
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('courtroom', $courtroom)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start);

        if ($ignoreHearingId) {
            $q->where('id', '!=', $ignoreHearingId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'courtroom' => 'Тухайн танхимд энэ хугацаанд өөр хурал бүртгэгдсэн байна (давхцаж байна).',
            ]);
        }

        if (count($judgeIds) > 0) {
            $q2 = Hearing::query()
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->whereHas('judges', fn ($w) => $w->whereIn('users.id', $judgeIds));

            if ($ignoreHearingId) {
                $q2->where('id', '!=', $ignoreHearingId);
            }

            if ($q2->exists()) {
                throw ValidationException::withMessages([
                    'presiding_judge_id' => 'Сонгосон шүүгчийн нэг дээр энэ хугацаанд өөр хурал давхцаж байна.',
                ]);
            }
        }

        if (count($lawyerNames) > 0) {
            $names = array_values(array_filter(array_unique($lawyerNames)));

            $q3 = Hearing::query()
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->where(function ($w) use ($names) {
                    foreach ($names as $name) {
                        $w->orWhereJsonContains('defendant_lawyers_text', $name)
                          ->orWhereJsonContains('victim_lawyers_text', $name)
                          ->orWhereJsonContains('victim_legal_rep_lawyers_text', $name)
                          ->orWhereJsonContains('civil_plaintiff_lawyers', $name)
                          ->orWhereJsonContains('civil_defendant_lawyers', $name);
                    }
                });

            if ($ignoreHearingId) {
                $q3->where('id', '!=', $ignoreHearingId);
            }

            if ($q3->exists()) {
                throw ValidationException::withMessages([
                    'defendant_lawyers_text' => 'Сонгосон өмгөөлөгчийн нэг дээр энэ хугацаанд өөр хурал давхцаж байна.',
                ]);
            }
        }

        if (count($prosecutorIds) > 0) {
            $ids = array_values(array_filter(array_unique(array_map('intval', $prosecutorIds))));

            $q4 = Hearing::query()
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<', $end)
                ->where('end_at', '>', $start)
                ->where(function ($w) use ($ids) {
                    $w->whereIn('prosecutor_id', $ids);
                    foreach ($ids as $id) {
                        $w->orWhereJsonContains('prosecutor_ids', $id);
                    }
                });

            if ($ignoreHearingId) {
                $q4->where('id', '!=', $ignoreHearingId);
            }

            if ($q4->exists()) {
                throw ValidationException::withMessages([
                    'prosecutor_ids' => 'Сонгосон Улсын яллагчийн нэг дээр энэ хугацаанд өөр хурал давхцаж байна.',
                ]);
            }
        }
    }

    protected function syncJudgesPivot(Hearing $hearing, Request $request): void
    {
        $presiding = (int) $request->input('presiding_judge_id');
        $m1 = $request->input('member_judge_1_id') ? (int) $request->input('member_judge_1_id') : null;
        $m2 = $request->input('member_judge_2_id') ? (int) $request->input('member_judge_2_id') : null;

        $arr = array_filter([$presiding, $m1, $m2]);
        if (count($arr) !== count(array_unique($arr))) {
            throw ValidationException::withMessages([
                'member_judge_1_id' => 'Шүүгч сонголт дээр давхардсан байна.',
            ]);
        }

        $sync = [$presiding => ['position' => 1]];
        if ($m1) $sync[$m1] = ['position' => 2];
        if ($m2) $sync[$m2] = ['position' => 3];

        $hearing->judges()->sync($sync);
    }
}
