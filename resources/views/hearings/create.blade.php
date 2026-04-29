@extends('layouts.dashboard')
@section('header','Хурлын зар оруулах')

@section('content')
@php
    $initialHearingDate = old('hearing_date') ?: now()->format('Y-m-d');
@endphp
<style>
    .hearing-create-wrap { width: 100%; }
    .hearing-panel-right { float: right; width: 320px; margin-left: 24px; margin-bottom: 24px; }
    .hearing-form-left { margin-right: 344px; min-width: 0; }
    @media (max-width: 640px) {
        .hearing-panel-right { float: none; width: 100%; margin-left: 0; margin-bottom: 24px; }
        .hearing-form-left { margin-right: 0; }
    }
</style>
<div class="mx-auto w-full px-2 sm:px-0" x-data="{
    selectedDate: '{{ $initialHearingDate }}',
    sourceHearingId: '{{ old('reschedule_source_hearing_id', $prefillSourceHearingId ?? '') }}',
    dayHearings: [],
    loading: false,
    byDateUrl: '{{ $byDateUrl }}',
    async fetchDay() {
        if (!this.selectedDate) { this.dayHearings = []; return; }
        this.loading = true;
        try {
            const params = new URLSearchParams({ date: this.selectedDate });
            if (this.sourceHearingId) {
                params.set('ignore_id', this.sourceHearingId);
            }
            const r = await fetch(this.byDateUrl + '?' + params.toString());
            const data = await r.json();
            this.dayHearings = Array.isArray(data) ? data : [];
        } catch (e) { this.dayHearings = []; }
        this.loading = false;
    }
}" x-init="$watch('selectedDate', () => fetchDay()); $nextTick(() => { if (selectedDate) fetchDay(); });">
    <div class="hearing-create-wrap">
        {{-- Баруун талд: Тухайн өдрийн хурал (float-аар баруун) --}}
        <div class="hearing-panel-right">
            <div class="sticky top-4 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm min-h-[200px]">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Тухайн өдрийн хурал</h3>
                <p x-show="!selectedDate" class="text-sm text-gray-500">Хурлын огноо сонгоно уу.</p>
                <template x-if="selectedDate && loading">
                    <p class="text-sm text-gray-500">Уншиж байна...</p>
                </template>
                <div x-show="selectedDate && !loading" class="space-y-2" x-cloak>
                    <p x-show="dayHearings.length === 0" class="text-sm text-gray-500">Энэ өдөр хурал байхгүй.</p>
                    <template x-for="h in dayHearings" :key="h.id">
                        <a :href="'{{ $editBaseUrl }}/' + h.id + '/edit'" class="block p-3 rounded-lg border border-gray-100 hover:bg-gray-50 text-sm space-y-1">
                            <div><span class="text-gray-500">Хурлын цаг:</span> <span class="font-semibold text-gray-800" x-text="h.start_time"></span></div>
                            <div><span class="text-gray-500">Танхим:</span> <span class="text-gray-700" x-text="h.courtroom_label"></span></div>
                            <div><span class="text-gray-500">Шүүгчийн нэр:</span> <span class="text-gray-600 text-xs" x-text="h.judge_names || '—'"></span></div>
                            <div><span class="text-gray-500">Улсын яллагч:</span> <span class="text-gray-600 text-xs" x-text="h.prosecutor_name || '—'"></span></div>
                            <div><span class="text-gray-500">Шүүгдэгчийн өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.defendant_lawyers_text || '—'"></span></div>
                            <div x-show="h.victim_lawyers_text"><span class="text-gray-500">Хохирогчийн өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.victim_lawyers_text"></span></div>
                            <div x-show="h.victim_legal_rep_lawyers_text"><span class="text-gray-500">Хууль ёсны төлөөлөгчийн өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.victim_legal_rep_lawyers_text"></span></div>
                            <div x-show="h.civil_plaintiff_lawyers"><span class="text-gray-500">Иргэний нэхэмжлэгчийн өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.civil_plaintiff_lawyers"></span></div>
                            <div x-show="h.civil_defendant_lawyers"><span class="text-gray-500">Иргэний хариуцагчийн өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.civil_defendant_lawyers"></span></div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
        {{-- Зүүн: Форм --}}
        <div class="hearing-form-left">
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-lg border border-red-200 bg-red-50 text-red-800">
                    <div class="font-semibold mb-1">Алдаа:</div>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Товч мэдээлэл — form‑оос өмнө --}}
            <div
                x-data="hearingUX()"
                x-init="init()"
                class="space-y-3 mb-4"
            >
                <div class="p-3 rounded-lg border bg-gray-50 text-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-semibold">Товч мэдээлэл</div>
                            <div class="text-gray-600">
                                Эхлэх: <span class="font-medium" x-text="startText"></span> |
                                Дуусах: <span class="font-medium" x-text="endText"></span> |
                                Хугацаа: <span class="font-medium" x-text="duration"></span> минут
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="isPanel ? 'bg-indigo-100 text-indigo-800' : 'bg-green-100 text-green-800'"
                              x-text="isPanel ? 'Бүрэлдэхүүнтэй хурал (60 мин)' : 'Бүрэлдэхүүнгүй хурал (30 мин)'">
                        </span>
                    </div>

                    <template x-if="conflictMessage">
                        <div class="mt-2 text-red-700 font-medium" x-text="conflictMessage"></div>
                    </template>
                </div>
            </div>

            <form method="POST" action="{{ $formAction }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
                @csrf

                {{-- 0) Үндсэн мэдээлэл --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700" for="case_no">Хэргийн дугаар</label>
                        <input id="case_no" name="case_no" required value="{{ old('case_no') }}"
                               class="mt-1 w-full rounded-md border px-3 py-2 {{ $errors->has('case_no') ? 'border-red-500 ring-2 ring-red-100' : 'border-gray-300' }}"
                               placeholder="Ж: 2026/БЗ/123">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700" for="hearing_state">Хурлын төлөв</label>
                        <select id="hearing_state" name="hearing_state" required class="mt-1 w-full rounded-md border px-3 py-2 {{ $errors->has('hearing_state') ? 'border-red-500 ring-2 ring-red-100' : 'border-gray-300' }}">
                            @foreach(['Хэвийн','Урьдчилсан хэлэлцүүлэг','Эрүүгийн хариуцлага','Хаалттай','Гэм буруугүй','Ял солих','Залруулга'] as $state)
                                <option value="{{ $state }}" @selected(old('hearing_state', 'Хэвийн') === $state)>{{ $state }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Default: Хэвийн</p>
                    </div>
                </div>
                
                {{-- 3-5) Огноо/Цаг/Танхим --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700" for="hearing_date_ui">Хурлын огноо</label>
                        <input type="hidden" name="hearing_date" :value="selectedDate">
                        <input id="hearing_date_ui" type="date" x-model="selectedDate" value="{{ $initialHearingDate }}" required
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    </div>

            <div>
                <label class="text-sm font-semibold text-gray-700" for="hour">Цаг (8–17)</label>
                <select id="hour" name="hour" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @for($h=8;$h<=17;$h++)
                        <option value="{{ $h }}" @selected((int)old('hour')===$h)>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700" for="minute">Минут (00–50)</label>
                <select id="minute" name="minute" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach([0,10,20,30,40,50] as $m)
                        <option value="{{ $m }}" @selected((int)old('minute')===$m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700" for="courtroom">Танхим</label>
                <select id="courtroom" name="courtroom" required data-conflict-key="courtroom"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach(['A','Б','В','Г','Д','Е','Ё','Ж'] as $room)
                        <option value="{{ $room }}" @selected(old('courtroom')===$room)>{{ $room }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Танхим/цаг давхцлыг систем автоматаар шалгана.</p>
            </div>
        </div>

        {{-- 6) Шүүх бүрэлдэхүүн (max 3) --}}
        <div
  x-data="judgePanelExclude({
    judges: @js($judges->map(fn($j)=>['id'=>(string)$j->id,'name'=>$j->name])->values()),
    presiding: '{{ $resolvedPresidingJudgeId ?? old('presiding_judge_id', $prefillPresidingJudgeId ?? '') }}',
    m1: '{{ $resolvedMemberJudge1Id ?? old('member_judge_1_id', $prefillMemberJudge1Id ?? '') }}',
    m2: '{{ $resolvedMemberJudge2Id ?? old('member_judge_2_id', $prefillMemberJudge2Id ?? '') }}',
  })"
  x-init="init()"
  class="space-y-4"
>

  {{-- Даргалагч --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1" for="presiding_judge_id">Даргалагч шүүгч</label>
    <select id="presiding_judge_id" name="presiding_judge_id" data-conflict-key="judges"
            x-model="presiding"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>
      <template x-for="j in judges" :key="j.id">
        <option :value="j.id" :selected="String(j.id) === String(presiding)" x-text="j.name"></option>
      </template>
    </select>
  </div>

  {{-- Гишүүн 1 --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1" for="member_judge_1_id">Шүүгч</label>
    <select id="member_judge_1_id" name="member_judge_1_id" data-conflict-key="judges"
            x-model="m1"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>

      {{-- ✅ энд judges биш availableForM1() дээр явна --}}
      <template x-for="j in availableForM1()" :key="j.id">
        <option :value="j.id" :selected="String(j.id) === String(m1)" x-text="j.name"></option>
      </template>
    </select>
  </div>

  {{-- Гишүүн 2 --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1" for="member_judge_2_id">Шүүгч</label>
    <select id="member_judge_2_id" name="member_judge_2_id" data-conflict-key="judges"
            x-model="m2"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>

      {{-- ✅ энд judges биш availableForM2() дээр явна --}}
      <template x-for="j in availableForM2()" :key="j.id">
        <option :value="j.id" :selected="String(j.id) === String(m2)" x-text="j.name"></option>
      </template>
    </select>
  </div>

</div>

<script>
function judgePanelExclude({ judges, presiding, m1, m2 }) {
  return {
    judges,
    presiding: presiding ? String(presiding) : '',
    m1: m1 ? String(m1) : '',
    m2: m2 ? String(m2) : '',

    init() {
      this.$nextTick(() => {
        const presidingEl = document.getElementById('presiding_judge_id');
        if (presidingEl && this.presiding) presidingEl.value = String(this.presiding);
        const m1El = document.getElementById('member_judge_1_id');
        if (m1El && this.m1) m1El.value = String(this.m1);
        const m2El = document.getElementById('member_judge_2_id');
        if (m2El && this.m2) m2El.value = String(this.m2);
      });
      this.changed();
    },

    // ✅ Давхардал гарвал автоматаар цэвэрлэнэ
    changed() {
      // presiding өөрчлөгдвөл гишүүд дээр давхардвал цэвэрлэнэ
      if (this.m1 && this.m1 === this.presiding) this.m1 = '';
      if (this.m2 && this.m2 === this.presiding) this.m2 = '';

      // m1/m2 давхардвал m2-г цэвэрлэнэ (эсвэл хүсвэл м1-г)
      if (this.m1 && this.m2 && this.m1 === this.m2) this.m2 = '';
    },

    // ✅ M1 дээр: presiding + m2-г хасна (харин одоо сонгосон m1 өөрөө харагдах ёстой)
    availableForM1() {
      return this.judges.filter(j => {
        const id = String(j.id);
        if (id === this.m1) return true;
        return id !== this.presiding && id !== this.m2;
      });
    },

    // ✅ M2 дээр: presiding + m1-г хасна
    availableForM2() {
      return this.judges.filter(j => {
        const id = String(j.id);
        if (id === this.m2) return true;
        return id !== this.presiding && id !== this.m1;
      });
    },
  }
}
</script>

<script>
function chipSelect(config) {
  return {
    options: Array.isArray(config.options) ? JSON.parse(JSON.stringify(config.options)) : [],
    selected: Array.isArray(config.selected) ? JSON.parse(JSON.stringify(config.selected)) : [],
    single: !!config.single,
    placeholder: config.placeholder || 'Сонгох...',
    nameId: config.nameId || 'ids[]',
    searchDigitsInNameToo: !!config.searchDigitsInNameToo,
    group: config.group || null,
    respectDefendantExclusions: !!config.respectDefendantExclusions,
    query: '',
    open: false,
    filteredOptions: [],
    openNow() {
      this.open = true;
      this.refreshFiltered();
      this.$nextTick(() => {
        this.refreshFiltered();
        requestAnimationFrame(() => this.refreshFiltered());
      });
    },
    init() {
      if (this.single && this.selected.length > 1) this.selected = this.selected.slice(0, 1);
      this.refreshFiltered();
      this.$watch('query', () => this.refreshFiltered());
      this.$watch('open', (v) => { if (v) this.$nextTick(() => this.refreshFiltered()); });
      const forceRender = () => {
        this.selected = [...this.selected];
        this.filteredOptions = [...this.filteredOptions];
        this.refreshFiltered();
      };
      this.$nextTick(forceRender);
      requestAnimationFrame(() => this.$nextTick(forceRender));
      setTimeout(forceRender, 50);
      setTimeout(forceRender, 200);
      document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') forceRender(); });
    },
    refreshFiltered() {
      const raw = (this.query || '').trim();
      const q = raw.toLowerCase();
      if (!q) {
        let base = [...this.options];
        if (this.respectDefendantExclusions && Array.isArray(window.defendantLawyerSelected)) {
          base = base.filter(o => !window.defendantLawyerSelected.includes(String(o.id)));
        }
        this.filteredOptions = base;
        return;
      }
      const digitsOnly = /^[0-9]+$/.test(raw);
      let base = this.options;
      if (this.respectDefendantExclusions && Array.isArray(window.defendantLawyerSelected)) {
        base = base.filter(o => !window.defendantLawyerSelected.includes(String(o.id)));
      }
      this.filteredOptions = base.filter(o => {
        const name = String(o.name || '').toLowerCase();
        const id = String(o.id ?? '').toLowerCase();
        if (digitsOnly && !this.searchDigitsInNameToo) {
          return id.includes(q);
        }
        return name.includes(q) || id.includes(q);
      });
    },
    selectedNamesText() {
      return this.selected.map(s => s.name).join(', ') || '';
    },
    listOptions() {
      return this.filteredOptions;
    },
    isSelected(opt) {
      return this.selected.some(s => s.id === opt.id);
    },
    toggle(opt) {
      if (this.single) {
        this.selected = [{ id: opt.id, name: opt.name }];
        this.open = false;
        this.query = '';
        this.refreshFiltered();
        if (this.group === 'defendant') {
          window.defendantLawyerSelected = this.selected.map(s => String(s.id));
        }
        return;
      }
      if (this.isSelected(opt)) {
        this.selected = this.selected.filter(s => s.id !== opt.id);
      } else {
        this.selected = [...this.selected, { id: opt.id, name: opt.name }];
      }
      if (this.group === 'defendant') {
        window.defendantLawyerSelected = this.selected.map(s => String(s.id));
      }
    },
    remove(s) {
      this.selected = this.selected.filter(x => x.id !== s.id);
      if (this.group === 'defendant') {
        window.defendantLawyerSelected = this.selected.map(x => String(x.id));
      }
    }
  };
}
</script>
        {{-- 7) Шүүгдэгч(ид) — API хайлт popup + autosize жагсаалт --}}
        @php
            $oldDefendantNames = old('defendant_names', []);
            $oldDefendantRegistries = old('defendant_registries', []);
            if (empty($oldDefendantNames) && is_string(old('defendants')) && trim(old('defendants')) !== '') {
                $oldDefendantNames = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', old('defendants')))));
            }
            $initialDefendants = collect($oldDefendantNames)->values()->map(function ($name, $index) use ($oldDefendantRegistries) {
                return [
                    'name' => $name,
                    'registry' => trim((string) ($oldDefendantRegistries[$index] ?? '')),
                ];
            })->values();
        @endphp
        <div
            class="flex flex-wrap items-end gap-3"
            x-data="{
                defendants: @js($initialDefendants),
                openModal: false,
                activeTab: 'person',
                registry: '',
                organizationName: '',
                loading: false,
                results: [],
                message: '',
                searchUrl: '{{ $defendantSearchUrl ?? route("admin.defendant-search") }}',
                addDefendant(item) {
                    if (!item || !item.name) return;
                    if (this.defendants.some(d => d.name === item.name && d.registry === (item.registry || ''))) return;
                    this.defendants.push({ name: item.name, registry: item.registry || '' });
                },
                addManualOrganization() {
                    const name = (this.organizationName || '').trim();
                    if (!name) { this.message = 'Байгууллагын нэр оруулна уу.'; return; }
                    if (this.defendants.some(d => (d.name || '').trim().toLowerCase() === name.toLowerCase())) {
                        this.message = 'Энэ нэртэй байгууллага аль хэдийн нэмэгдсэн байна.';
                        return;
                    }
                    this.defendants.push({ name, registry: '' });
                    this.organizationName = '';
                    this.message = '';
                    this.openModal = false;
                },
                removeDefendant(index) { this.defendants.splice(index, 1); },
                async search() {
                    const reg = (this.registry || '').trim();
                    if (!reg) { this.message = 'Регистрийн дугаар оруулна уу.'; this.results = []; return; }
                    this.loading = true; this.message = ''; this.results = [];
                    try {
                        const r = await fetch(this.searchUrl + '?registry=' + encodeURIComponent(reg));
                        const data = await r.json();
                        this.results = data.results || [];
                        if (this.results.length === 0) this.message = data.message || 'Олдсонгүй.';
                    } catch (e) { this.message = 'Хайлт амжилтгүй.'; }
                    this.loading = false;
                }
            }"
        >
            <div class="flex-1 min-w-[200px]">
                <label class="text-sm font-semibold text-gray-700">Шүүгдэгч</label>
                <div class="mt-1 min-h-[2.5rem] rounded-md border bg-gray-50/50 p-2 flex flex-wrap items-center gap-2 {{ ($errors->has('defendant_names') || $errors->has('defendant_names.0')) ? 'border-red-500 ring-2 ring-red-100' : 'border-gray-300' }}">
                    <template x-for="(d, i) in defendants" :key="i">
                        <span class="inline-flex items-center gap-1.5 bg-white border border-gray-300 rounded-lg px-2.5 py-1 text-sm shadow-sm">
                            <span x-text="d.registry ? d.name + ' (' + d.registry + ')' : d.name"></span>
                            <button type="button" @click="removeDefendant(i)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                        </span>
                    </template>
                </div>
            </div>
            <div class="shrink-0">
                <button type="button"
                        @click="openModal = true; activeTab = 'person'; registry = ''; organizationName = ''; results = []; message = '';"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <span>+</span>
                    <span>Шүүгдэгч нэмэх</span>
                </button>
            </div>
            <template x-for="(d, i) in defendants" :key="'h-'+i">
                    <input type="hidden" :name="'defendant_names['+i+']'" :value="d.name">
                </template>
            <template x-for="(d, i) in defendants" :key="'r-'+i">
                    <input type="hidden" :name="'defendant_registries['+i+']'" :value="d.registry || ''">
                </template>

                {{-- Popup: Регистрийн дугаараар хайлт --}}
                <div x-show="openModal"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                     @keydown.escape.window="openModal = false"
                >
                    <div @click.outside="openModal = false"
                         class="bg-white rounded-2xl shadow-xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden"
                         role="dialog"
                         aria-label="Шүүгдэгч хайх">
                        <div class="px-5 py-4 border-b bg-slate-50 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-slate-900">Шүүгдэгч оруулах</h3>
                            <button type="button" @click="openModal = false" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
                        </div>
                        <div class="p-5 space-y-4 overflow-y-auto">
                            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1">
                                <button type="button"
                                        @click="activeTab = 'person'"
                                        :class="activeTab === 'person' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
                                    Иргэн
                                </button>
                                <button type="button"
                                        @click="activeTab = 'organization'"
                                        :class="activeTab === 'organization' ? 'bg-blue-700 text-white' : 'text-slate-600 hover:bg-slate-100'"
                                        class="px-4 py-1.5 text-sm font-medium rounded-md transition-colors">
                                    Байгууллага
                                </button>
                            </div>

                            <div x-show="activeTab === 'person'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                <label class="block text-sm font-semibold text-slate-700">Иргэн (регистрийн дугаараар хайх)</label>
                                <div class="flex gap-2">
                                    <input type="text"
                                           x-model="registry"
                                           @keydown.enter.prevent="search()"
                                           placeholder="Регистрийн дугаар"
                                           class="flex-1 rounded-md border border-slate-300 px-3 py-2 bg-white">
                                    <button type="button"
                                            @click="search()"
                                            :disabled="loading"
                                            class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800 disabled:opacity-50">
                                        <span x-text="loading ? 'Уншиж...' : 'Хайх'"></span>
                                    </button>
                                </div>
                                <p x-show="message" x-text="message" class="text-sm text-amber-700"></p>
                                <div class="border border-slate-200 rounded-lg overflow-auto max-h-56 bg-white">
                                    <template x-for="(item, idx) in results" :key="idx">
                                        <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 hover:bg-slate-50 last:border-0">
                                            <div>
                                                <span class="font-medium text-slate-800" x-text="item.name"></span>
                                                <span x-show="item.registry" class="text-slate-500 text-sm ml-1" x-text="'(' + item.registry + ')'"></span>
                                            </div>
                                            <button type="button"
                                                    @click="addDefendant(item); openModal = false"
                                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                                Сонгох
                                            </button>
                                        </div>
                                    </template>
                                    <p x-show="!loading && results.length === 0 && !message" class="px-3 py-4 text-slate-500 text-sm">Регистрийн дугаар оруулаад Хайх дарна уу.</p>
                                </div>
                            </div>
                            <div x-show="activeTab === 'organization'" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                <label class="block text-sm font-semibold text-slate-700">Байгууллага (нэрээр гараар оруулах)</label>
                                <div class="flex gap-2">
                                    <input type="text"
                                           x-model="organizationName"
                                           @keydown.enter.prevent="addManualOrganization()"
                                           placeholder="Байгууллагын нэр"
                                           class="flex-1 rounded-md border border-slate-300 px-3 py-2 bg-white">
                                    <button type="button"
                                            @click="addManualOrganization()"
                                            class="px-4 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-100 text-sm font-medium text-slate-700">
                                        Нэмэх
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="px-5 py-4 border-t bg-slate-50 flex justify-end">
                            <button type="button" @click="openModal = false" class="px-4 py-2 rounded-md border border-slate-300 bg-white hover:bg-slate-100">Хаах</button>
                        </div>
                    </div>
                </div>
        </div>

        @php
            $searchUrl = $defendantSearchUrl ?? route('admin.defendant-search');
            $toInitial = function ($key, $registryKey = null, $fallbackKey = null) {
                $arr = old($key, []);
                if (!is_array($arr)) $arr = [];
                $registries = $registryKey ? old($registryKey, []) : [];
                if (!is_array($registries)) $registries = [];
                if (empty($arr) && $fallbackKey && is_string(old($fallbackKey)) && trim(old($fallbackKey)) !== '') {
                    $arr = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', old($fallbackKey)))));
                }
                return collect($arr)->values()->map(function ($n, $i) use ($registries) {
                    return ['name' => $n, 'registry' => trim((string)($registries[$i] ?? ''))];
                })->values();
            };
            $initialVictims = $toInitial('victim_names', 'victim_registries', 'victim_name');
            $initialVictimLegalReps = $toInitial('victim_legal_rep_names', 'victim_legal_rep_registries', 'victim_legal_rep');
            $initialWitnesses = $toInitial('witness_names', 'witness_registries', 'witnesses');
            $initialExperts = $toInitial('expert_names', null, 'experts');
            $initialCivilPlaintiffs = $toInitial('civil_plaintiff_names', 'civil_plaintiff_registries', 'civil_plaintiff');
            $initialCivilDefendants = $toInitial('civil_defendant_names', 'civil_defendant_registries', 'civil_defendant');
        @endphp

        {{-- Хохирогч + хууль ёсны төлөөлөгч — хэрэгтэй үед нэмэх --}}
        <div
            x-data="{
                showVictimRep: {{ $initialVictimLegalReps->isNotEmpty() ? 'true' : 'false' }},
            }"
            class="space-y-4"
        >
            @include('hearings.partials.person-search-row', [
                'initial' => $initialVictims,
                'nameKey' => 'victim_names',
                'label' => 'Хохирогч',
                'buttonLabel' => 'Хохирогч нэмэх',
                'modalTitle' => 'Хохирогч оруулах',
                'searchUrl' => $searchUrl,
                'registryKey' => 'victim_registries',
            ])

            <div x-show="showVictimRep" x-transition>
                @include('hearings.partials.person-search-row', [
                    'initial' => $initialVictimLegalReps,
                    'nameKey' => 'victim_legal_rep_names',
                    'label' => 'Хохирогчийн хууль ёсны төлөөлөгч',
                    'buttonLabel' => 'Хохирогчийн хууль ёсны төлөөлөгч нэмэх',
                    'modalTitle' => 'Хохирогчийн хууль ёсны төлөөлөгч оруулах',
                    'searchUrl' => $searchUrl,
                    'registryKey' => 'victim_legal_rep_registries',
                ])
            </div>

            <button type="button"
                    class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                    x-show="!showVictimRep"
                    @click="showVictimRep = true">
                + Хохирогчийн хууль ёсны төлөөлөгч нэмэх
            </button>
        </div>

        {{-- 8-9) Таслан сэргийлэх + Улсын яллагч (дараагийн мөрөнд) --}}
        <div class="grid grid-cols-1 gap-4">
        <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">
        Таслан сэргийлэх арга хэмжээ
    </label>

    @php
        $pm = old('preventive_measure', []);
        if (!is_array($pm)) {
            $pm = array_filter([trim((string)$pm)]);
        }
        $pmSelected = collect($pm)->filter()->values()->map(fn($v) => ['id' => $v, 'name' => $v])->values();
        $pmOptions = collect([
            'хувийн баталгаа гаргах',
            'тодорхой үйл ажиллагаа явуулах, албан үүргээ биелүүлэхийг түдгэлзүүлэх',
            'хязгаарлалт тогтоох',
            'барьцаа авах',
            'цагдан хорих',
            'цэргийн ангийн удирдлагад хянан харгалзуулах',
        ])->map(fn($v) => ['id' => $v, 'name' => $v])->values();
        $hasPreventiveError = collect($errors->keys())->contains(fn($k) => str_starts_with((string)$k, 'preventive_measure'));
        $preventiveErrorMessage = collect($errors->getMessages())->filter(fn($v, $k) => str_starts_with((string)$k, 'preventive_measure'))->flatten()->first();
    @endphp

        <div
        x-data="chipSelect({
            options: @js($pmOptions),
            selected: @js($pmSelected),
            single: false,
            placeholder: 'Арга хэмжээ хайх...',
            nameId: 'preventive_measure[]'
        })"
        x-init="init()"
        class="mt-1"
    >
        <div class="mt-1 rounded-md border bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400 {{ $hasPreventiveError ? 'border-red-500 ring-2 ring-red-100' : 'border-gray-300' }}"
             @click="openNow(); $refs.input?.focus()"
             @click.outside="open = false">
            <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                <template x-for="s in selected" :key="'pm-'+s.id">
                    <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                        <span x-text="s.name"></span>
                        <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                    </span>
                </template>
                <input type="text"
                       x-ref="input"
                       x-model="query"
                       :id="searchId()"
                       :name="searchName()"
                       autocomplete="off"
                       @focus="openNow()"
                       @click="openNow()"
                       @input="refreshFiltered()"
                       @keydown.escape="open = false"
                     
                       class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
            </div>
            <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                <template x-for="opt in filteredOptions" :key="opt.id">
                    <div @click="toggle(opt)"
                         class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                        <span x-text="opt.name"></span>
                        <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                    </div>
                </template>
                <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
            </div>
        </div>
        <template x-for="s in selected" :key="'pmh-'+s.id">
            <input type="hidden" :name="nameId" :value="s.id">
        </template>

        @if($preventiveErrorMessage ?? null)
            <p class="mt-1 text-sm text-red-600">{{ $preventiveErrorMessage }}</p>
        @endif
    </div>

            @php
                $oldProsecutorIds = old('prosecutor_ids', []);
                if (empty($oldProsecutorIds) && old('prosecutor_id')) {
                    $oldProsecutorIds = [(int)old('prosecutor_id')];
                }
                $oldProsecutorSelected = collect($oldProsecutorIds)->map(fn($id) => $prosecutors->firstWhere('id', (int)$id))->filter()->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name])->values();
            @endphp
            <div
                x-data="chipSelect({
                    options: @js($prosecutors->map(fn($p) => ['id' => (string)$p->id, 'name' => $p->name])->values()),
                    selected: @js($oldProsecutorSelected),
                    single: false,
                    placeholder: 'Прокурор хайх...',
                    nameId: 'prosecutor_ids[]'
                })"
                x-init="init()"
                class="mt-1" data-conflict-key="prosecutor_ids">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Улсын яллагч</label>
                <div class="mt-1 rounded-md border bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400 {{ ($errors->has('prosecutor_ids') || $errors->has('prosecutor_ids.0') || $errors->has('prosecutor_id')) ? 'border-red-500 ring-2 ring-red-100' : 'border-gray-300' }}"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Прокурор хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
            </div>
        </div>

        {{-- Зүйл анги (олон сонголт, хайлт) --}}
        <div
            x-data="chipSelect({
                options: @js($matterCategories->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name])->values()),
                selected: @js(collect(old('matter_category_ids', []))->map(fn($id) => ['id' => (string)$id, 'name' => $matterCategories->firstWhere('id', $id)?->name ?? ''])->filter(fn($s) => $s['name'] !== '')->values()),
                single: false,
                placeholder: 'Зүйл анги хайх...',
                nameId: 'matter_category_ids[]',
                searchDigitsInNameToo: true
            })"
            x-init="init()"
            class="mt-1">
            <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Зүйл анги</label>
            <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                 @click="openNow(); $refs.input?.focus()"
                 @click.outside="open = false">
                <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                    <template x-for="s in selected" :key="'c-'+s.id">
                        <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                            <span x-text="s.name"></span>
                            <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                        </span>
                    </template>
                    <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                           placeholder="Зүйл анги хайх..."
                           class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                </div>
                <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                    <template x-for="opt in filteredOptions" :key="opt.id">
                        <div @click="toggle(opt)"
                             class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                            <span x-text="opt.name"></span>
                            <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                        </div>
                    </template>
                    <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                </div>
            </div>
            <template x-for="s in selected" :key="'h-'+s.id">
                <input type="hidden" :name="nameId" :value="s.id">
            </template>
        </div>

        {{-- 10-14) Өмгөөлөгчид — үндсэн + сонголтоор нэмэгдэх талбарууд --}}
        <div
            x-data="{
                showVictimLawyer: {{ collect(old('victim_lawyers_text', []))->isNotEmpty() ? 'true' : 'false' }},
                showVictimRepLawyer: {{ collect(old('victim_legal_rep_lawyers_text', []))->isNotEmpty() ? 'true' : 'false' }},
                showCivilPlaintiffLawyer: {{ collect(old('civil_plaintiff_lawyers', []))->isNotEmpty() ? 'true' : 'false' }},
                showCivilDefendantLawyer: {{ collect(old('civil_defendant_lawyers', []))->isNotEmpty() ? 'true' : 'false' }},
            }"
            class="space-y-4"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('defendant_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Нэрээр хайх...',
                    nameId: 'defendant_lawyers_text[]',
                    group: 'defendant'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Шүүгдэгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Нэрээр хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
                </div>

                <div
                    x-show="showVictimLawyer"
                    x-transition
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('victim_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Нэрээр хайх...',
                    nameId: 'victim_lawyers_text[]',
                    respectDefendantExclusions: true
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Хохирогчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Нэрээр хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
                </div>

                <div
                    x-show="showVictimRepLawyer"
                    x-transition
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('victim_legal_rep_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Хууль ёсны төлөөлөгчийн өмгөөлөгч хайх...',
                    nameId: 'victim_legal_rep_lawyers_text[]',
                    respectDefendantExclusions: true
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Хохирогчийн хууль ёсны төлөөлөгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Нэрээр хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
                </div>
            </div>

            @php
                $oldCivilPlaintiff = old('civil_plaintiff_lawyers', []);
                if (is_string($oldCivilPlaintiff)) {
                    $oldCivilPlaintiff = array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $oldCivilPlaintiff))));
                }
                $oldCivilPlaintiffSelected = collect(is_array($oldCivilPlaintiff) ? $oldCivilPlaintiff : [])->map(fn($n) => ['id' => $n, 'name' => $n])->values();
            @endphp
            <div
                x-show="showCivilPlaintiffLawyer"
                x-transition
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($oldCivilPlaintiffSelected),
                    single: false,
                    placeholder: 'Иргэний нэхэмжлэгчийн өмгөөлөгч хайх...',
                    nameId: 'civil_plaintiff_lawyers[]',
                    respectDefendantExclusions: true
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Иргэний нэхэмжлэгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Нэрээр хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
            </div>

            @php
                $oldCivilDefendant = old('civil_defendant_lawyers', []);
                if (is_string($oldCivilDefendant)) {
                    $oldCivilDefendant = array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $oldCivilDefendant))));
                }
                $oldCivilDefendantSelected = collect(is_array($oldCivilDefendant) ? $oldCivilDefendant : [])->map(fn($n) => ['id' => $n, 'name' => $n])->values();
            @endphp
            <div
                x-show="showCivilDefendantLawyer"
                x-transition
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($oldCivilDefendantSelected),
                    single: false,
                    placeholder: 'Иргэний хариуцагчийн өмгөөлөгч хайх...',
                    nameId: 'civil_defendant_lawyers[]',
                    respectDefendantExclusions: true
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Иргэний хариуцагчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border border-gray-300 bg-white focus-within:ring-2 focus-within:ring-blue-100 focus-within:border-blue-400"
                     @click="openNow(); $refs.input?.focus()"
                     @click.outside="open = false">
                    <div class="flex flex-wrap items-center gap-2 px-2 py-1.5 min-h-[2.5rem]">
                        <template x-for="s in selected" :key="'c-'+s.id">
                            <span class="inline-flex items-center gap-1 bg-gray-100 border border-gray-300 rounded px-2 py-0.5 text-sm">
                                <span x-text="s.name"></span>
                                <button type="button" @click.stop="remove(s)" class="text-gray-500 hover:text-red-600 leading-none">&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="input" x-model="query" :id="searchId()" :name="searchName()" autocomplete="off" @focus="openNow()" @click="openNow()" @input="refreshFiltered()" @keydown.escape="open = false"
                               placeholder="Нэрээр хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" x-cloak class="border-t border-gray-200 max-h-48 overflow-auto">
                        <template x-for="opt in filteredOptions" :key="opt.id">
                            <div @click="toggle(opt)"
                                 class="flex items-center justify-between px-3 py-2 cursor-pointer hover:bg-gray-100 text-sm border-b border-gray-50 last:border-0">
                                <span x-text="opt.name"></span>
                                <span x-show="isSelected(opt)" class="text-gray-800 font-bold">✓</span>
                            </div>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500 text-sm">Олдсонгүй</div>
                    </div>
                </div>
                <template x-for="s in selected" :key="'h-'+s.id">
                    <input type="hidden" :name="nameId" :value="s.id">
                </template>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showVictimLawyer"
                        @click="showVictimLawyer = true">
                    + Хохирогчийн өмгөөлөгч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showVictimRepLawyer"
                        @click="showVictimRepLawyer = true">
                    + Хууль ёсны төлөөлөгчийн өмгөөлөгч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showCivilPlaintiffLawyer"
                        @click="showCivilPlaintiffLawyer = true">
                    + Иргэний нэхэмжлэгчийн өмгөөлөгч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showCivilDefendantLawyer"
                        @click="showCivilDefendantLawyer = true">
                    + Иргэний хариуцагчийн өмгөөлөгч нэмэх
                </button>
            </div>
        </div>

        {{-- Гэрч, Шинжээч, Иргэний нэхэмжлэгч, Хариуцагч — хэрэгтэй үедээ нэмэх --}}
        <div
            x-data="{
                showWitness: {{ $initialWitnesses->isNotEmpty() ? 'true' : 'false' }},
                showExpert: {{ $initialExperts->isNotEmpty() ? 'true' : 'false' }},
                showCivilPlaintiff: {{ $initialCivilPlaintiffs->isNotEmpty() ? 'true' : 'false' }},
                showCivilDefendant: {{ $initialCivilDefendants->isNotEmpty() ? 'true' : 'false' }},
            }"
            class="space-y-4"
        >
            <div x-show="showWitness" x-transition>
                @include('hearings.partials.person-search-row', [
                    'initial' => $initialWitnesses,
                    'nameKey' => 'witness_names',
                    'label' => 'Гэрч',
                    'buttonLabel' => 'Гэрч нэмэх',
                    'modalTitle' => 'Гэрч оруулах',
                    'searchUrl' => $searchUrl,
                'registryKey' => 'witness_registries',
                ])
            </div>

            <div x-show="showExpert" x-transition>
                @include('hearings.partials.manual-names-row', [
                    'initial' => $initialExperts,
                    'nameKey' => 'expert_names',
                    'label' => 'Шинжээч',
                    'buttonLabel' => 'Шинжээч нэмэх',
                ])
            </div>

            <div x-show="showCivilPlaintiff" x-transition>
                @include('hearings.partials.person-search-row', [
                    'initial' => $initialCivilPlaintiffs,
                    'nameKey' => 'civil_plaintiff_names',
                    'label' => 'Иргэний нэхэмжлэгч',
                    'buttonLabel' => 'Иргэний нэхэмжлэгч нэмэх',
                    'modalTitle' => 'Иргэний нэхэмжлэгч оруулах',
                    'searchUrl' => $searchUrl,
                'registryKey' => 'civil_plaintiff_registries',
                ])
            </div>

            <div x-show="showCivilDefendant" x-transition>
                @include('hearings.partials.person-search-row', [
                    'initial' => $initialCivilDefendants,
                    'nameKey' => 'civil_defendant_names',
                    'label' => 'Иргэний хариуцагч',
                    'buttonLabel' => 'Иргэний хариуцагч нэмэх',
                    'modalTitle' => 'Иргэний хариуцагч оруулах',
                    'searchUrl' => $searchUrl,
                'registryKey' => 'civil_defendant_registries',
                ])
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showWitness"
                        @click="showWitness = true">
                    + Гэрч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showExpert"
                        @click="showExpert = true">
                    + Шинжээч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showCivilPlaintiff"
                        @click="showCivilPlaintiff = true">
                    + Иргэний нэхэмжлэгч нэмэх
                </button>
                <button type="button"
                        class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white hover:bg-gray-50"
                        x-show="!showCivilDefendant"
                        @click="showCivilDefendant = true">
                    + Иргэний хариуцагч нэмэх
                </button>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="px-5 py-2.5 rounded-md bg-blue-900 text-white hover:bg-blue-800">
                Хурлын зар бүртгэх
            </button>
            <a href="{{ $backUrl }}"
               class="px-5 py-2.5 rounded-md bg-gray-100 border border-gray-200 hover:bg-gray-200">
                Буцах
            </a>
        </div>
    </form>
        </div>
    </div>
    {{-- ✅ JS --}}
    <script>
        function hearingUX(){
            return {
                date: document.querySelector('[name="hearing_date"]')?.value || '',
                hour: document.querySelector('[name="hour"]')?.value || '',
                minute: document.querySelector('[name="minute"]')?.value || '',
                courtroom: document.querySelector('[name="courtroom"]')?.value || '',
                presiding: document.querySelector('[name="presiding_judge_id"]')?.value || '',
                m1: document.querySelector('[name="member_judge_1_id"]')?.value || '',
                m2: document.querySelector('[name="member_judge_2_id"]')?.value || '',
                duration: 10,
                isPanel: false,
                startText: '-',
                endText: '-',
                conflictMessage: '',
                conflictField: '',

                init(){
                    // input listener-үүд
                    ['hearing_date','hour','minute','courtroom','presiding_judge_id','member_judge_1_id','member_judge_2_id']
                        .forEach(n=>{
                            const el = document.querySelector(`[name="${n}"]`);
                            if(!el) return;
                            el.addEventListener('change', () => { this.read(); this.compute(); this.checkConflict(); });
                            el.addEventListener('keyup', () => { this.read(); this.compute(); this.checkConflict(); });
                        });

                    this.read(); this.compute(); this.checkConflict();
                },

                paintConflict(field){
                    const errClasses = ['border-red-500','ring-2','ring-red-100'];
                    document.querySelectorAll('[data-conflict-key]').forEach(el => el.classList.remove(...errClasses));
                    if(!field) return;
                    // Map specific field keys to UI keys
                    const uiKey =
                        (field === 'courtroom') ? 'courtroom'
                        : (['presiding_judge_id','member_judge_1_id','member_judge_2_id'].includes(field) ? 'judges'
                        : (field === 'prosecutor_ids' || field === 'prosecutor_id') ? 'prosecutor_ids'
                        : field);
                    document.querySelectorAll(`[data-conflict-key="${uiKey}"]`).forEach(el => el.classList.add(...errClasses));
                },

                read(){
                    this.date = document.querySelector('[name="hearing_date"]')?.value || '';
                    this.hour = document.querySelector('[name="hour"]')?.value || '';
                    this.minute = document.querySelector('[name="minute"]')?.value || '';
                    this.courtroom = document.querySelector('[name="courtroom"]')?.value || '';
                    this.presiding = document.querySelector('[name="presiding_judge_id"]')?.value || '';
                    this.m1 = document.querySelector('[name="member_judge_1_id"]')?.value || '';
                    this.m2 = document.querySelector('[name="member_judge_2_id"]')?.value || '';
                },

                compute(){
                    const judgeIds = [this.presiding, this.m1, this.m2].filter(Boolean);
                    const uniq = [...new Set(judgeIds)];
                    this.isPanel = uniq.length >= 3;
                    this.duration = this.isPanel ? 30 : 10;

                    if(!this.date || this.hour === '' || this.minute === ''){
                        this.startText = '-'; this.endText = '-';
                        return;
                    }

                    const pad = (n)=> String(n).padStart(2,'0');
                    const start = `${this.date} ${pad(this.hour)}:${pad(this.minute)}`;
                    this.startText = start;

                    // end time (client-side rough)
                    const d = new Date(`${this.date}T${pad(this.hour)}:${pad(this.minute)}:00`);
                    d.setMinutes(d.getMinutes() + this.duration);
                    const end = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
                    this.endText = end;
                },

                async checkConflict(){
                    this.conflictMessage = '';
                    this.conflictField = '';
                    this.paintConflict('');
                    if(!this.date || this.hour === '' || this.minute === '' || !this.courtroom || !this.presiding) return;

                    const arr = (name) => Array.from(document.querySelectorAll(`input[name="${name}"]`)).map(i => i.value).filter(Boolean);
                    const payload = {
                        hearing_date: this.date,
                        hour: Number(this.hour),
                        minute: Number(this.minute),
                        courtroom: this.courtroom,
                        presiding_judge_id: Number(this.presiding),
                        member_judge_1_id: this.m1 ? Number(this.m1) : null,
                        member_judge_2_id: this.m2 ? Number(this.m2) : null,
                        prosecutor_ids: Array.from(document.querySelectorAll('input[name="prosecutor_ids[]"]')).map(i => Number(i.value)).filter(v => !Number.isNaN(v)),
                        defendant_lawyers_text: arr('defendant_lawyers_text[]'),
                        victim_lawyers_text: arr('victim_lawyers_text[]'),
                        victim_legal_rep_lawyers_text: arr('victim_legal_rep_lawyers_text[]'),
                        civil_plaintiff_lawyers: arr('civil_plaintiff_lawyers[]'),
                        civil_defendant_lawyers: arr('civil_defendant_lawyers[]'),
                    };

                    const res = await fetch("{{ $checkConflictUrl }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();
                    if(!data.ok){
                        this.conflictMessage = data.message || "Давхцал илэрлээ.";
                        this.conflictField = data.field || '';
                        this.paintConflict(this.conflictField);
                    }
                }
            }
        }
    </script>
</div>
{{-- Өмгөөлөгчдийн талбарууд одоо системд бүртгэлтэй lawyer role хэрэглэгчдээс олон сонголт хийдэг --}}
@endsection
