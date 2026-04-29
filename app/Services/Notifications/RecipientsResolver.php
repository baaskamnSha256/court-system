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

        $users = $users->merge($this->resolveNamedUsers((array) $hearing->defendant_lawyers_text, 'defendant_lawyer'));
        $users = $users->merge($this->resolveNamedUsers((array) $hearing->victim_lawyers_text, 'victim_lawyer'));
        $users = $users->merge($this->resolveNamedUsers((array) $hearing->victim_legal_rep_lawyers_text, 'victim_legal_rep_lawyer'));
        $users = $users->merge($this->resolveNamedUsers((array) $hearing->civil_plaintiff_lawyers, 'civil_plaintiff_lawyer'));
        $users = $users->merge($this->resolveNamedUsers((array) $hearing->civil_defendant_lawyers, 'civil_defendant_lawyer'));

        $namedRecipients = $users
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
                    'civil_id' => null,
                ];
            });

        $defendantRecipients = $this->buildRoleRecipientsByRegistries(
            (array) $hearing->defendant_names,
            (array) ($hearing->defendant_registries ?? []),
            'defendant'
        );

        // Доорх талбарууд project дээр байх үед (migration хийж нэмсэн бол) автоматаар ашиглагдана.
        $victimRecipients = $this->buildRoleRecipientsByRegistries(
            $this->parseTextNames((string) ($hearing->victim_name ?? '')),
            (array) ($hearing->victim_registries ?? []),
            'victim'
        );
        $victimLegalRepRecipients = $this->buildRoleRecipientsByRegistries(
            $this->parseTextNames((string) ($hearing->victim_legal_rep ?? '')),
            (array) ($hearing->victim_legal_rep_registries ?? []),
            'victim_legal_rep'
        );
        $witnessRecipients = $this->buildRoleRecipientsByRegistries(
            $this->parseTextNames((string) ($hearing->witnesses ?? '')),
            (array) ($hearing->witness_registries ?? []),
            'witness'
        );
        $civilPlaintiffRecipients = $this->buildRoleRecipientsByRegistries(
            $this->parseTextNames((string) ($hearing->civil_plaintiff ?? '')),
            (array) ($hearing->civil_plaintiff_registries ?? []),
            'civil_plaintiff'
        );
        $civilDefendantRecipients = $this->buildRoleRecipientsByRegistries(
            $this->parseTextNames((string) ($hearing->civil_defendant ?? '')),
            (array) ($hearing->civil_defendant_registries ?? []),
            'civil_defendant'
        );

        return $namedRecipients
            ->merge($defendantRecipients)
            ->merge($victimRecipients)
            ->merge($victimLegalRepRecipients)
            ->merge($witnessRecipients)
            ->merge($civilPlaintiffRecipients)
            ->merge($civilDefendantRecipients)
            ->unique(fn (array $recipient) => ($recipient['role'] ?? '').'#'.mb_strtolower((string) ($recipient['regnum'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function parseTextNames(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[\n,]+/u', $value) ?: [])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveNamedUsers(array $names, string $role): \Illuminate\Support\Collection
    {
        $normalizedNames = collect($names)
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn ($name) => trim((string) $name))
            ->unique()
            ->values();

        if ($normalizedNames->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->whereIn('name', $normalizedNames->all())
            ->whereNotNull('register_number')
            ->get();

        return $users->map(fn (User $user) => ['role' => $role, 'user' => $user]);
    }

    private function buildRoleRecipientsByRegistries(array $names, array $registries, string $role): \Illuminate\Support\Collection
    {
        return collect($names)
            ->map(function ($name, $index) use ($registries, $role): ?array {
                $normalizedName = is_string($name) ? trim($name) : '';
                if ($normalizedName === '') {
                    return null;
                }

                $regnum = trim((string) ($registries[$index] ?? ''));
                if ($regnum === '') {
                    return null;
                }

                return [
                    'role' => $role,
                    'name' => $normalizedName,
                    'phone' => null,
                    'regnum' => $regnum,
                    'civil_id' => null,
                ];
            })
            ->filter()
            ->values();
    }
}
