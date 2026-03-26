@extends('layouts.dashboard')
@section('header','Хурлын зар оруулах')

@section('content')
<div class="mx-auto" x-data="{
    selectedDate: '{{ old('hearing_date') }}',
    dayHearings: [],
    loading: false,
    byDateUrl: '{{ $byDateUrl }}',
    async fetchDay() {
        if (!this.selectedDate) { this.dayHearings = []; return; }
        this.loading = true;
        try {
            const r = await fetch(this.byDateUrl + '?date=' + encodeURIComponent(this.selectedDate));
            const data = await r.json();
            this.dayHearings = Array.isArray(data) ? data : [];
        } catch (e) { this.dayHearings = []; }
        this.loading = false;
    }
}" x-init="$watch('selectedDate', () => fetchDay()); $nextTick(() => { if (selectedDate) fetchDay(); });">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Зүүн: Форм --}}
        <div class="lg:col-span-2">
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

            <form method="POST" action="{{ $formAction }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
                @csrf

                {{-- 0) Үндсэн мэдээлэл --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700">Хэргийн дугаар (сонголтоор)</label>
                        <input name="case_no" value="{{ old('case_no') }}"
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                               placeholder="Ж: 2026/БЗ/123">
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700">Хурлын төлөв</label>
                        <select name="hearing_state" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                            @foreach(['Хэвийн','Урьдчилсан хэлэлцүүлэг','Эрүүгийн хариуцлага','Хаалттай','Гэм буруугүй'] as $state)
                                <option value="{{ $state }}" @selected(old('hearing_state', 'Хэвийн') === $state)>{{ $state }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Default: Хэвийн</p>
                    </div>
                </div>
                
                {{-- 3-5) Огноо/Цаг/Танхим --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700">Хурлын огноо</label>
                        <input type="hidden" name="hearing_date" :value="selectedDate">
                        <input type="date" x-model="selectedDate" required
                               class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Цаг (8–17
                    
                )</label>
                <select name="hour" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @for($h=8;$h<=18;$h++)
                        <option value="{{ $h }}" @selected((int)old('hour')===$h)>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Минут (00–50)</label>
                <select name="minute" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach([0,10,20,30,40,50] as $m)
                        <option value="{{ $m }}" @selected((int)old('minute')===$m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Танхим</label>
                <select name="courtroom" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
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
    presiding: '{{ old('presiding_judge_id', '') }}',
    m1: '{{ old('member_judge_1_id', '') }}',
    m2: '{{ old('member_judge_2_id', '') }}',
  })"
  x-init="init()"
  class="space-y-4"
>

  {{-- Даргалагч --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">Даргалагч шүүгч</label>
    <select name="presiding_judge_id"
            x-model="presiding"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>
      <template x-for="j in judges" :key="j.id">
        <option :value="j.id" x-text="j.name"></option>
      </template>
    </select>
  </div>

  {{-- Гишүүн 1 --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">Шүүгч</label>
    <select name="member_judge_1_id"
            x-model="m1"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>

      {{-- ✅ энд judges биш availableForM1() дээр явна --}}
      <template x-for="j in availableForM1()" :key="j.id">
        <option :value="j.id" x-text="j.name"></option>
      </template>
    </select>
  </div>

  {{-- Гишүүн 2 --}}
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">Шүүгч</label>
    <select name="member_judge_2_id"
            x-model="m2"
            @change="changed()"
            class="w-full border rounded-md px-3 py-2">
      <option value="">-- Сонгох --</option>

      {{-- ✅ энд judges биш availableForM2() дээр явна --}}
      <template x-for="j in availableForM2()" :key="j.id">
        <option :value="j.id" x-text="j.name"></option>
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
        this.filteredOptions = [...this.options];
        return;
      }
      const digitsOnly = /^[0-9]+$/.test(raw);
      this.filteredOptions = this.options.filter(o => {
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
        return;
      }
      if (this.isSelected(opt)) {
        this.selected = this.selected.filter(s => s.id !== opt.id);
      } else {
        this.selected = [...this.selected, { id: opt.id, name: opt.name }];
      }
    },
    remove(s) {
      this.selected = this.selected.filter(x => x.id !== s.id);
    }
  };
}
</script>
        {{-- 7) Шүүгдэгч — нэг мөр: талбар + Шүүгдэгч нэмэх товч --}}
        <div class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[200px]">
                <label class="text-sm font-semibold text-gray-700">Шүүгдэгч</label>
                <textarea name="defendants" rows="1"
                          class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                          placeholder="Нэг мөрөнд нэг хүн / эсвэл таслалаар">{{ old('defendants') }}</textarea>
            </div>
            <div class="shrink-0">
                <button type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <span>+</span>
                    <span>Шүүгдэгч нэмэх</span>
                </button>
            </div>
        </div>

        @php
            $searchUrl = $defendantSearchUrl ?? route('admin.defendant-search');
            $toInitial = function ($key, $fallbackKey = null) {
                $arr = old($key, []);
                if (!is_array($arr)) $arr = [];
                if (empty($arr) && $fallbackKey && is_string(old($fallbackKey)) && trim(old($fallbackKey)) !== '') {
                    $arr = array_values(array_filter(array_map('trim', preg_split('/[\n,]+/', old($fallbackKey)))));
                }
                return collect($arr)->map(fn($n) => ['name' => $n, 'registry' => ''])->values();
            };
            $initialVictims = $toInitial('victim_names', 'victim_name');
            $initialVictimLegalReps = $toInitial('victim_legal_rep_names', 'victim_legal_rep');
            $initialWitnesses = $toInitial('witness_names', 'witnesses');
            $initialExperts = $toInitial('expert_names', 'experts');
            $initialCivilPlaintiffs = $toInitial('civil_plaintiff_names', 'civil_plaintiff');
            $initialCivilDefendants = $toInitial('civil_defendant_names', 'civil_defendant');
        @endphp

        @include('hearings.partials.person-search-row', [
            'initial' => $initialVictims,
            'nameKey' => 'victim_names',
            'label' => 'Хохирогч',
            'buttonLabel' => 'Хохирогч нэмэх',
            'modalTitle' => 'Хохирогч оруулах',
            'searchUrl' => $searchUrl,
        ])
        @include('hearings.partials.person-search-row', [
            'initial' => $initialVictimLegalReps,
            'nameKey' => 'victim_legal_rep_names',
            'label' => 'Хохирогчийн хууль ёсны төлөөлөгч',
            'buttonLabel' => 'Хохирогчийн хууль ёсны төлөөлөгч нэмэх',
            'modalTitle' => 'Хохирогчийн хууль ёсны төлөөлөгч оруулах',
            'searchUrl' => $searchUrl,
        ])

        {{-- 8-9) Таслан сэргийлэх + Улсын яллагч --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
    <label class="block text-sm font-semibold text-gray-700 mb-1">
        Таслан сэргийлэх арга хэмжээ
    </label>

    <select name="preventive_measure"
            class="w-full border rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500">
        <option value="">-- Сонгохгүй (хоосон) --</option>

        @php
            $pm = old('preventive_measure');
        @endphp

        <option value="хувийн баталгаа гаргах" @selected($pm==='хувийн баталгаа гаргах')>
            Хувийн баталгаа гаргах
        </option>
        <option value="тодорхой үйл ажиллагаа явуулах, албан үүргээ биелүүлэхийг түдгэлзүүлэх"
                @selected($pm==='тодорхой үйл ажиллагаа явуулах, албан үүргээ биелүүлэхийг түдгэлзүүлэх')>
            Тодорхой үйл ажиллагаа явуулах, албан үүргээ биелүүлэхийг түдгэлзүүлэх
        </option>
        <option value="хязгаарлалт тогтоох" @selected($pm==='хязгаарлалт тогтоох')>
            Хязгаарлалт тогтоох
        </option>
        <option value="барьцаа авах" @selected($pm==='барьцаа авах')>
            Барьцаа авах
        </option>
        <option value="цагдан хорих" @selected($pm==='цагдан хорих')>
            Цагдан хорих
        </option>
        <option value="цэргийн ангийн удирдлагад хянан харгалзуулах"
                @selected($pm==='цэргийн ангийн удирдлагад хянан харгалзуулах')>
            Цэргийн ангийн удирдлагад хянан харгалзуулах
        </option>
    </select>

    @error('preventive_measure')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
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
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Улсын яллагч</label>
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
                               placeholder="Прокурор хайх..."
                               class="flex-1 min-w-[8rem] border-0 py-1 focus:ring-0 focus:outline-none">
                    </div>
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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

        {{-- 10-14) Өмгөөлөгчид — нэг талбар: chip + хайлт, олон сонголт --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('defendant_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Нэрээр хайх...',
                    nameId: 'defendant_lawyers_text[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Шүүгдэгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
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
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('victim_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Нэрээр хайх...',
                    nameId: 'victim_lawyers_text[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Хохирогчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
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
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js(collect(old('victim_legal_rep_lawyers_text', []))->map(fn($n) => ['id' => $n, 'name' => $n])->values()),
                    single: false,
                    placeholder: 'Хууль ёсны төлөөлөгчийн өмгөөлөгч хайх...',
                    nameId: 'victim_legal_rep_lawyers_text[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Хохирогчийн хууль ёсны төлөөлөгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
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
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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
                $oldCivilPlaintiff = old('civil_plaintiff_lawyers', []);
                if (is_string($oldCivilPlaintiff)) {
                    $oldCivilPlaintiff = array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $oldCivilPlaintiff))));
                }
                $oldCivilPlaintiffSelected = collect(is_array($oldCivilPlaintiff) ? $oldCivilPlaintiff : [])->map(fn($n) => ['id' => $n, 'name' => $n])->values();
            @endphp
            <div
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($oldCivilPlaintiffSelected),
                    single: false,
                    placeholder: 'Иргэний нэхэмжлэгчийн өмгөөлөгч хайх...',
                    nameId: 'civil_plaintiff_lawyers[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Иргэний нэхэмжлэгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
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
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($oldCivilDefendantSelected),
                    single: false,
                    placeholder: 'Иргэний хариуцагчийн өмгөөлөгч хайх...',
                    nameId: 'civil_defendant_lawyers[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Иргэний хариуцагчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
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
                    <div x-show="open" class="border-t border-gray-200 max-h-48 overflow-auto">
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

        @include('hearings.partials.person-search-row', [
            'initial' => $initialWitnesses,
            'nameKey' => 'witness_names',
            'label' => 'Гэрч',
            'buttonLabel' => 'Гэрч нэмэх',
            'modalTitle' => 'Гэрч оруулах',
            'searchUrl' => $searchUrl,
        ])
        @include('hearings.partials.manual-names-row', [
            'initial' => $initialExperts,
            'nameKey' => 'expert_names',
            'label' => 'Шинжээч',
            'buttonLabel' => 'Шинжээч нэмэх',
        ])
        @include('hearings.partials.person-search-row', [
            'initial' => $initialCivilPlaintiffs,
            'nameKey' => 'civil_plaintiff_names',
            'label' => 'Иргэний нэхэмжлэгч',
            'buttonLabel' => 'Иргэний нэхэмжлэгч нэмэх',
            'modalTitle' => 'Иргэний нэхэмжлэгч оруулах',
            'searchUrl' => $searchUrl,
        ])
        @include('hearings.partials.person-search-row', [
            'initial' => $initialCivilDefendants,
            'nameKey' => 'civil_defendant_names',
            'label' => 'Иргэний хариуцагч',
            'buttonLabel' => 'Иргэний хариуцагч нэмэх',
            'modalTitle' => 'Иргэний хариуцагч оруулах',
            'searchUrl' => $searchUrl,
        ])

        {{-- Тэмдэглэл --}}
        <div>
            <label class="text-sm font-semibold text-gray-700">Нэмэлт тэмдэглэл (сонголтоор)</label>
            <textarea name="note" rows="3"
                      class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                      placeholder="...">{{ old('note') }}</textarea>
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

        {{-- Баруун: Тухайн өдрийн хурал --}}
        <div class="lg:col-span-1">
            <div class="sticky top-4 bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Тухайн өдрийн хурал</h3>
                <template x-if="!selectedDate">
                    <p class="text-sm text-gray-500">Хурлын огноо сонгоно уу.</p>
                </template>
                <template x-if="selectedDate && loading">
                    <p class="text-sm text-gray-500">Уншиж байна...</p>
                </template>
                <div x-show="selectedDate && !loading" class="space-y-2" x-cloak>
                    <template x-if="dayHearings.length === 0">
                        <p class="text-sm text-gray-500">Энэ өдөр хурл байхгүй.</p>
                    </template>
                    <template x-for="h in dayHearings" :key="h.id">
                        <a :href="'{{ $editBaseUrl }}/' + h.id + '/edit'" class="block p-3 rounded-lg border border-gray-100 hover:bg-gray-50 text-sm space-y-1">
                            <div><span class="text-gray-500">Хурлын цаг:</span> <span class="font-semibold text-gray-800" x-text="h.start_time"></span></div>
                            <div><span class="text-gray-500">Танхим:</span> <span class="text-gray-700" x-text="h.courtroom_label"></span></div>
                            <div><span class="text-gray-500">Шүүгчийн нэр:</span> <span class="text-gray-600 text-xs" x-text="h.judge_names || '—'"></span></div>
                            <div><span class="text-gray-500">Улсын яллагч:</span> <span class="text-gray-600 text-xs" x-text="h.prosecutor_name || '—'"></span></div>
                            <div><span class="text-gray-500">Өмгөөлөгч:</span> <span class="text-gray-600 text-xs" x-text="h.lawyer_names || '—'"></span></div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    x-data="hearingUX()"
    x-init="init()"
    class="space-y-3"
>
    {{-- ✅ Live summary --}}
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
                duration: 30,
                isPanel: false,
                startText: '-',
                endText: '-',
                conflictMessage: '',

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
                    this.duration = this.isPanel ? 60 : 30;

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
                    if(!this.date || this.hour === '' || this.minute === '' || !this.courtroom || !this.presiding) return;

                    const payload = {
                        hearing_date: this.date,
                        hour: Number(this.hour),
                        minute: Number(this.minute),
                        courtroom: this.courtroom,
                        presiding_judge_id: Number(this.presiding),
                        member_judge_1_id: this.m1 ? Number(this.m1) : null,
                        member_judge_2_id: this.m2 ? Number(this.m2) : null,
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
                    }
                }
            }
        }
    </script>
</div>
{{-- Өмгөөлөгчдийн талбарууд одоо системд бүртгэлтэй lawyer role хэрэглэгчдээс олон сонголт хийдэг --}}
@endsection
