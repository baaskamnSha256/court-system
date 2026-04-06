@extends('layouts.dashboard')
@section('header','Хурлын зар засварлах')

@section('content')
<div class="max-w-5xl mx-auto">

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

    @php
        $judgesCol = $hearing->relationLoaded('judges') ? $hearing->judges : collect();
        $presiding = optional($judgesCol->first(fn($j) => optional($j->pivot)->position == 1))->id;
        $m1 = optional($judgesCol->first(fn($j) => optional($j->pivot)->position == 2))->id;
        $m2 = optional($judgesCol->first(fn($j) => optional($j->pivot)->position == 3))->id;

        $join = fn($arr) => is_array($arr) ? implode(', ', $arr) : '';
    @endphp

    <form method="POST" action="{{ route('admin.hearings.update', $hearing) }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Хэргийн дугаар (сонголтоор)</label>
                <input name="case_no" value="{{ old('case_no', $hearing->case_no) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Хурлын төлөв</label>
                <select name="hearing_state" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach(['Хэвийн','Урьдчилсан хэлэлцүүлэг','Эрүүгийн хариуцлага','Хаалттай','Гэм буруугүй','Ял солих','Залруулга'] as $state)
                        <option value="{{ $state }}" @selected(old('hearing_state', $hearing->hearing_state ?? 'Хэвийн') === $state)>{{ $state }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Хурлын огноо</label>
                <input type="date" name="hearing_date" required value="{{ old('hearing_date', optional($hearing->hearing_date)->format('Y-m-d') ?? $hearing->hearing_date) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Цаг (8–17)</label>
                <select name="hour" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @for($h=8;$h<=18;$h++)
                        <option value="{{ $h }}" @selected((int)old('hour', $hearing->hour)===$h)>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Минут (00–50)</label>
                <select name="minute" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach([0,10,20,30,40,50] as $m)
                        <option value="{{ $m }}" @selected((int)old('minute', $hearing->minute)===$m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Танхим</label>
                <select name="courtroom" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach(['A','Б','В','Г','Д','Е','Ё','Ж'] as $room)
                        <option value="{{ $room }}" @selected(old('courtroom', $hearing->courtroom)===$room)>{{ $room }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div
  x-data="judgePanelExclude({
    judges: @js($judges->map(fn($j)=>['id'=>(string)$j->id,'name'=>$j->name])->values()),
    presiding: '{{ old('presiding_judge_id', $presiding ?? '') }}',
    m1: '{{ old('member_judge_1_id', $m1 ?? '') }}',
    m2: '{{ old('member_judge_2_id', $m2 ?? '') }}',
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
    <label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн шүүгч 1</label>
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
    <label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн шүүгч 2</label>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Таслан сэргийлэх арга хэмжээ</label>
                <input name="preventive_measure" value="{{ old('preventive_measure', $hearing->preventive_measure) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Улсын яллагч</label>
                <select name="prosecutor_name" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach(['Бат','Дорж','Сүхээ'] as $p)
                        <option value="{{ $p }}" @selected(old('prosecutor_name', $hearing->prosecutor_name)===$p)>{{ $p }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Шүүгдэгчийн өмгөөлөгч (олон)</label>
                <select name="defendant_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @php
                        $selectedDefLawyers = collect(old('defendant_lawyers_text', $hearing->defendant_lawyers_text ?? []));
                    @endphp
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected($selectedDefLawyers->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Хохирогчийн өмгөөлөгч (олон)</label>
                <select name="victim_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @php
                        $selectedVictimLawyers = collect(old('victim_lawyers_text', $hearing->victim_lawyers_text ?? []));
                    @endphp
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected($selectedVictimLawyers->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Хохирогчийн хууль ёсны төлөөлөгчийн өмгөөлөгч</label>
                <select name="victim_legal_rep_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @php
                        $selectedVictimRepLawyers = collect(old('victim_legal_rep_lawyers_text', $hearing->victim_legal_rep_lawyers_text ?? []));
                    @endphp
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected($selectedVictimRepLawyers->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @php
                $editCivilPlaintiff = old('civil_plaintiff_lawyers');
                if ($editCivilPlaintiff === null) {
                    $editCivilPlaintiff = $hearing->civil_plaintiff_lawyers ?? [];
                }
                if (is_string($editCivilPlaintiff)) {
                    $editCivilPlaintiff = array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $editCivilPlaintiff))));
                }
                $editCivilPlaintiffSelected = collect(is_array($editCivilPlaintiff) ? $editCivilPlaintiff : [])->map(fn($n) => ['id' => $n, 'name' => $n])->values();
            @endphp
            <div
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($editCivilPlaintiffSelected),
                    single: false,
                    placeholder: 'Иргэний нэхэмжлэгчийн өмгөөлөгч хайх...',
                    nameId: 'civil_plaintiff_lawyers[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Иргэний нэхэмжлэгчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
                     @mousedown.prevent.stop="openNow(); $refs.input?.focus()">
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
                    <div x-show="open" @click.outside="open = false" class="border-t border-gray-200 max-h-48 overflow-auto">
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
                $editCivilDefendant = old('civil_defendant_lawyers');
                if ($editCivilDefendant === null) {
                    $editCivilDefendant = $hearing->civil_defendant_lawyers ?? [];
                }
                if (is_string($editCivilDefendant)) {
                    $editCivilDefendant = array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $editCivilDefendant))));
                }
                $editCivilDefendantSelected = collect(is_array($editCivilDefendant) ? $editCivilDefendant : [])->map(fn($n) => ['id' => $n, 'name' => $n])->values();
            @endphp
            <div
                x-data="chipSelect({
                    options: @js($lawyers->map(fn($l) => ['id' => $l->name, 'name' => $l->name])->values()),
                    selected: @js($editCivilDefendantSelected),
                    single: false,
                    placeholder: 'Иргэний хариуцагчийн өмгөөлөгч хайх...',
                    nameId: 'civil_defendant_lawyers[]'
                })"
                x-init="init()"
                class="mt-1">
                <label class="block text-sm font-semibold text-gray-700 mb-1" :for="searchId()">Иргэний хариуцагчийн өмгөөлөгч</label>
                <div class="mt-1 rounded-md border-2 border-green-500 bg-white focus-within:ring-2 focus-within:ring-green-200 focus-within:border-green-500"
                     @mousedown.prevent.stop="openNow(); $refs.input?.focus()">
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
                    <div x-show="open" @click.outside="open = false" class="border-t border-gray-200 max-h-48 overflow-auto">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Шүүгдэгч(ид)</label>
                <textarea name="defendants" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('defendants', $hearing->defendants) }}</textarea>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Хохирогч</label>
                <input name="victim_name" value="{{ old('victim_name', $hearing->victim_name) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                <div class="mt-3">
                    <label class="text-sm font-semibold text-gray-700">Хохирогчийн хууль ёсны төлөөлөгч</label>
                    <input name="victim_legal_rep" value="{{ old('victim_legal_rep', $hearing->victim_legal_rep) }}"
                           class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Гэрч</label>
                <textarea name="witnesses" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('witnesses', $hearing->witnesses) }}</textarea>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Шинжээч</label>
                <textarea name="experts" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('experts', $hearing->experts) }}</textarea>
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Иргэний нэхэмжлэгч</label>
                <input name="civil_plaintiff" value="{{ old('civil_plaintiff', $hearing->civil_plaintiff) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-700">Иргэний хариуцагч</label>
                <input name="civil_defendant" value="{{ old('civil_defendant', $hearing->civil_defendant) }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Нэмэлт тэмдэглэл</label>
            <textarea name="note" rows="3" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('note', $hearing->note) }}</textarea>
        </div>

        <div class="flex gap-3">
            <button class="px-5 py-2.5 rounded-md bg-blue-900 text-white hover:bg-blue-800">
                Шинэчлэх
            </button>
            <a href="{{ route('admin.hearings.index') }}"
               class="px-5 py-2.5 rounded-md bg-gray-100 border border-gray-200 hover:bg-gray-200">
                Буцах
            </a>
        </div>
    </form>
</div>

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
@endsection
