<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Hearing extends Model
{
    protected $fillable = [
        'created_by',
        'case_no',
        'title',
        'matter_category',
        'matter_category_ids',
        'hearing_type',
        'session_openness',
        'hearing_state',
        'hearing_date',
        'hour',
        'minute',
        'start_at',
        'end_at',
        'duration_minutes',
        'courtroom',
        'preventive_measure',
        'prosecutor_id',
        'prosecutor_ids',
        'defendant_names',
        'defendant_registries',
        'victim_registries',
        'victim_legal_rep_registries',
        'witness_registries',
        'civil_plaintiff_registries',
        'civil_defendant_registries',
        'defendant_lawyers_text',
        'victim_lawyers_text',
        'victim_legal_rep_lawyers_text',
        'defendant_lawyers',
        'victim_lawyers',
        'victim_legal_rep_lawyers',
        'civil_plaintiff_lawyers',
        'civil_defendant_lawyers',
        'defendants',
        'victim_name',
        'witnesses',
        'experts',
        'victim_legal_rep',
        'civil_plaintiff',
        'civil_defendant',
        'judge_names_text',
        'note',
        'status',
        'judge_id',
        'secretary_id',
        'lawyer_name',
        'defendant_name',
        'prosecutor_name',
        'clerk_id',
        'notes_handover_text',
        'notes_decided_matter',
        'notes_fine_units',
        'notes_damage_amount',
        'notes_decision_status',
        'notes_defendant_sentences',
        'notes_handover_issued',
        'notes_handover_issued_at',
        'notes_handover_saved_at',
        'notes_clerk_selected_at',
    ];

    protected $casts = [
        'defendant_names' => 'array',
        'defendant_registries' => 'array',
        'victim_registries' => 'array',
        'victim_legal_rep_registries' => 'array',
        'witness_registries' => 'array',
        'civil_plaintiff_registries' => 'array',
        'civil_defendant_registries' => 'array',
        'defendant_lawyers_text' => 'array',
        'victim_lawyers_text' => 'array',
        'victim_legal_rep_lawyers_text' => 'array',
        'defendant_lawyers' => 'array',
        'victim_lawyers' => 'array',
        'victim_legal_rep_lawyers' => 'array',
        'civil_plaintiff_lawyers' => 'array',
        'civil_defendant_lawyers' => 'array',
        'prosecutor_ids' => 'array',
        'matter_category_ids' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'hearing_date' => 'date',
        'notes_handover_issued' => 'bool',
        'notes_defendant_sentences' => 'array',
        'notes_handover_issued_at' => 'datetime',
        'notes_handover_saved_at' => 'datetime',
        'notes_clerk_selected_at' => 'datetime',
    ];

    public function judges()
    {
        return $this->belongsToMany(\App\Models\User::class, 'hearing_judges', 'hearing_id', 'judge_id')
            ->withPivot('position')
            ->orderBy('hearing_judges.position');
    }

    /**
     * Хурлыг дахин зарлахад шүүгчийн select-үүдийг бөглөх ID-ууд (1=даргалагч, дараагийнх нь гишүүд).
     *
     * @return list<int>
     */
    public function judgeSlotUserIdsOrdered(): array
    {
        $ids = DB::table('hearing_judges')
            ->where('hearing_id', $this->id)
            ->orderBy('position')
            ->pluck('judge_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === [] && ! empty($this->judge_id)) {
            $ids = [(int) $this->judge_id];
        }

        if ($ids === [] && ! empty($this->judge_names_text)) {
            $names = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/u', (string) $this->judge_names_text))));
            foreach ($names as $name) {
                $id = User::query()->where('name', $name)->value('id');
                if (! $id) {
                    $escaped = addcslashes($name, '%_');
                    $id = User::query()
                        ->where('name', 'like', "%{$escaped}%")
                        ->orderBy('id')
                        ->value('id');
                }
                if ($id) {
                    $ids[] = (int) $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function prosecutor()
    {
        return $this->belongsTo(\App\Models\User::class, 'prosecutor_id');
    }

    /** Прокуроруудын ID-ууд: prosecutor_ids ашиглана, хоосон бол prosecutor_id-аас уншина */
    public function getProsecutorIdsListAttribute(): array
    {
        $ids = $this->prosecutor_ids;
        if (! empty($ids)) {
            return array_values(array_filter(array_map('intval', $ids)));
        }
        if (! empty($this->prosecutor_id)) {
            return [(int) $this->prosecutor_id];
        }

        return [];
    }

    /** Прокуроруудын жагсаалт (User model) */
    public function prosecutors()
    {
        $ids = $this->prosecutor_ids_list;
        if (empty($ids)) {
            return collect();
        }

        return \App\Models\User::whereIn('id', $ids)->orderBy('name')->get();
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function clerk()
    {
        return $this->belongsTo(\App\Models\User::class, 'clerk_id');
    }

    /** Хурлын төлөв null бол Хэвийн гэж үзнэ */
    public function getHearingStateAttribute($value)
    {
        return $value ?? 'Хэвийн';
    }

    /** Зүйл ангиудын нэрс (ID-аар дамжуулан) */
    public function matterCategories()
    {
        $ids = $this->matter_category_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return \App\Models\MatterCategory::whereIn('id', $ids)->orderBy('sort_order')->get();
    }
}
