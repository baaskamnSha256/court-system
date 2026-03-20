@extends('layouts.dashboard')
@section('header', 'Хурлын зар оруулах')

@section('content')
<div class="mx-auto" x-data="{
    selectedDate: '{{ old('hearing_date') }}',
    dayHearings: [],
    loading: false,
    byDateUrl: '{{ route('secretary.hearings.byDate') }}',
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

    <form method="POST" action="{{ route('secretary.hearings.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Хэргийн дугаар</label>
                <input name="case_no" value="{{ old('case_no') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" placeholder="Ж: 2026/БЗ/123">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Хурлын нэр / тайлбар</label>
                <input name="title" required value="{{ old('title') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" placeholder="Ж: Урьдчилсан хэлэлцүүлэг">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Хурлын огноо</label>
                <input type="hidden" name="hearing_date" :value="selectedDate">
                <input type="date" x-model="selectedDate" required value="{{ old('hearing_date') }}"
                       class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Цаг</label>
                <select name="hour" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @for($h=8;$h<=18;$h++)
                        <option value="{{ $h }}" @selected((int)old('hour')===$h)>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Минут</label>
                <select name="minute" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach($minutes as $m)
                        <option value="{{ $m }}" @selected((int)old('minute')===$m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Танхим</label>
                <select name="courtroom" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach($courtrooms as $room)
                        <option value="{{ $room }}" @selected(old('courtroom')===$room)>{{ $room }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div x-data="judgePanelExclude({
            judges: @js($judges->map(fn($j)=>['id'=>(string)$j->id,'name'=>$j->name])->values()),
            presiding: '{{ old('presiding_judge_id','') }}',
            m1: '{{ old('member_judge_1_id','') }}',
            m2: '{{ old('member_judge_2_id','') }}',
        })" x-init="init()" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Даргалагч шүүгч</label>
                <select name="presiding_judge_id" x-model="presiding" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in judges" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн шүүгч 1</label>
                <select name="member_judge_1_id" x-model="m1" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in availableForM1()" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн шүүгч 2</label>
                <select name="member_judge_2_id" x-model="m2" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in availableForM2()" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Шүүгдэгчийн нэр (таслал эсвэл шинэ мөрөөр)</label>
            <textarea name="defendant_names_text" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" placeholder="А.Бат, Б.Дорж">{{ old('defendant_names_text') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Таслан сэргийлэх арга хэмжээ</label>
                <select name="preventive_measure" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">-- Сонгохгүй --</option>
                    @foreach($preventiveMeasures as $pm)
                        <option value="{{ $pm }}" @selected(old('preventive_measure')===$pm)>{{ $pm }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Улсын яллагч</label>
                <select name="prosecutor_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">- Сонгох -</option>
                    @foreach($prosecutors as $p)
                        <option value="{{ $p->id }}" @selected(old('prosecutor_id')==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Шүүгдэгчийн өмгөөлөгч (олон сонголт)</label>
                <select name="defendant_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected(collect(old('defendant_lawyers_text', []))->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Хохирогчийн өмгөөлөгч (олон сонголт)</label>
                <select name="victim_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected(collect(old('victim_lawyers_text', []))->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Хохирогчийн хууль ёсны төлөөлөгчийн өмгөөлөгч (олон сонголт)</label>
                <select name="victim_legal_rep_lawyers_text[]" multiple
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    @foreach($lawyers as $l)
                        <option value="{{ $l->name }}"
                            @selected(collect(old('victim_legal_rep_lawyers_text', []))->contains($l->name))>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Нэмэлт тэмдэглэл</label>
            <textarea name="note" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('note') }}</textarea>
        </div>

        <div x-data="hearingUX()" x-init="init()" class="space-y-3">
            <div class="p-3 rounded-lg border bg-gray-50 text-sm">
                <div class="font-semibold">Товч: Эхлэх <span x-text="startText"></span> | Дуусах <span x-text="endText"></span> | <span x-text="duration"></span> мин</div>
                <template x-if="conflictMessage"><div class="mt-2 text-red-700 font-medium" x-text="conflictMessage"></div></template>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2.5 rounded-md bg-blue-900 text-white hover:bg-blue-800">Хурлын зар бүртгэх</button>
            <a href="{{ route('secretary.hearings.index') }}" class="px-5 py-2.5 rounded-md bg-gray-100 border border-gray-200 hover:bg-gray-200">Буцах</a>
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
                        <a :href="'{{ url('/secretary/hearings') }}/' + h.id + '/edit'" class="block p-3 rounded-lg border border-gray-100 hover:bg-gray-50 text-sm space-y-1">
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

<script>
function judgePanelExclude({ judges, presiding, m1, m2 }) {
  return {
    judges,
    presiding: presiding ? String(presiding) : '',
    m1: m1 ? String(m1) : '',
    m2: m2 ? String(m2) : '',
    init() { this.changed(); },
    changed() {
      if (this.m1 && this.m1 === this.presiding) this.m1 = '';
      if (this.m2 && this.m2 === this.presiding) this.m2 = '';
      if (this.m1 && this.m2 && this.m1 === this.m2) this.m2 = '';
    },
    availableForM1() { return this.judges.filter(j => { const id = String(j.id); if (id === this.m1) return true; return id !== this.presiding && id !== this.m2; }); },
    availableForM2() { return this.judges.filter(j => { const id = String(j.id); if (id === this.m2) return true; return id !== this.presiding && id !== this.m1; }); },
  };
}
function hearingUX(){
  return {
    date: '', hour: '', minute: '', courtroom: '', presiding: '', m1: '', m2: '',
    duration: 30, startText: '-', endText: '-', conflictMessage: '',
    init(){
      ['hearing_date','hour','minute','courtroom','presiding_judge_id','member_judge_1_id','member_judge_2_id'].forEach(n=>{
        const el = document.querySelector(`[name="${n}"]`);
        if(el){ el.addEventListener('change', () => { this.read(); this.compute(); this.checkConflict(); }); }
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
      this.duration = (new Set(judgeIds)).size >= 3 ? 60 : 30;
      if(!this.date || this.hour === '' || this.minute === ''){ this.startText = '-'; this.endText = '-'; return; }
      const pad = n=> String(n).padStart(2,'0');
      this.startText = `${this.date} ${pad(this.hour)}:${pad(this.minute)}`;
      const d = new Date(`${this.date}T${pad(this.hour)}:${pad(this.minute)}:00`);
      d.setMinutes(d.getMinutes() + this.duration);
      this.endText = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    },
    async checkConflict(){
      this.conflictMessage = '';
      if(!this.date || this.hour === '' || this.minute === '' || !this.courtroom || !this.presiding) return;
      const payload = { hearing_date: this.date, hour: Number(this.hour), minute: Number(this.minute), courtroom: this.courtroom, presiding_judge_id: Number(this.presiding), member_judge_1_id: this.m1 ? Number(this.m1) : null, member_judge_2_id: this.m2 ? Number(this.m2) : null };
      const res = await fetch("{{ route('secretary.hearings.checkConflict') }}", { method: "POST", headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" }, body: JSON.stringify(payload) });
      const data = await res.json();
      if(!data.ok) this.conflictMessage = data.message || "Давхцал илэрлээ.";
    }
  };
}
</script>
@endsection
