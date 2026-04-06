<?php

namespace App\Services\Notifications;

use App\Models\Hearing;
use App\Models\User;

class RecipientsResolver
{
    /**
     * @return array<int, array{role:string,name:string,phone:?string,regnum:?string,civil_id:?string}>
     */
    public function resolve(Hearing $hearing): array
    {
        $hearing->loadMissing(['judges', 'prosecutor']);

        $users = collect();
        $users = $users->merge($hearing->judges->map(fn (User $u) => ['role' => 'judge', 'user' => $u]));
        $users = $users->merge($hearing->prosecutors()->map(fn (User $u) => ['role' => 'prosecutor', 'user' => $u]));

        $lawyerNames = collect([
            ...((array) $hearing->defendant_lawyers_text),
            ...((array) $hearing->victim_lawyers_text),
            ...((array) $hearing->victim_legal_rep_lawyers_text),
            ...((array) $hearing->civil_plaintiff_lawyers),
            ...((array) $hearing->civil_defendant_lawyers),
        ])->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn ($name) => trim((string) $name))
            ->unique()
            ->values();

        if ($lawyerNames->isNotEmpty()) {
            $lawyers = User::query()
                ->role('lawyer')
                ->whereIn('name', $lawyerNames->all())
                ->get();

            $users = $users->merge($lawyers->map(fn (User $u) => ['role' => 'lawyer', 'user' => $u]));
        }

        return $users
            ->unique(fn ($item) => $item['role'].'#'.$item['user']->id)
            ->map(function (array $item): array {
                /** @var User $user */
                $user = $item['user'];
                $regnum = $user->register_number ? trim((string) $user->register_number) : null;
                $phone = $user->phone ? trim((string) $user->phone) : null;

                return [
                    'role' => (string) $item['role'],
                    'name' => (string) $user->name,
                    'phone' => $phone,
                    'regnum' => $regnum,
                    // CivilId өгөгдөл тусдаа талбаргүй тул регистрээр түр нөхөж дамжуулна.
                    'civil_id' => $regnum,
                ];
            })
            ->values()
            ->all();
    }
}
