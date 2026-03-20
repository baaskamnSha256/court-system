@extends('layouts.dashboard')
@section('header', 'Хурлын зар оруулах')

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

    <form method="POST" action="{{ route('court_clerk.hearings.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Хэргийн дугаар</label>
                <input name="case_no" value="{{ old('case_no') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" placeholder="Ж: 2026/БЗ/123">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Хурлын нэр</label>
                <input name="title" required value="{{ old('title') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Огноо</label>
                <input type="date" name="hearing_date" required value="{{ old('hearing_date') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Цаг</label>
                <select name="hour" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">-</option>
                    @for($h=8;$h<=18;$h++)
                        <option value="{{ $h }}" @selected((int)old('hour')===$h)>{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Минут</label>
                <select name="minute" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">-</option>
                    @foreach($minutes as $m)
                        <option value="{{ $m }}" @selected((int)old('minute')===$m)>{{ str_pad($m,2,'0',STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Танхим</label>
                <select name="courtroom" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">-</option>
                    @foreach($courtrooms as $room)
                        <option value="{{ $room }}" @selected(old('courtroom')===$room)>{{ $room }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div x-data="judgePanelExclude({judges: @js($judges->map(fn($j)=>['id'=>(string)$j->id,'name'=>$j->name])->values()), presiding: '{{ old('presiding_judge_id','') }}', m1: '{{ old('member_judge_1_id','') }}', m2: '{{ old('member_judge_2_id','') }}'})" x-init="init()" class="space-y-4">
            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Даргалагч шүүгч</label>
                <select name="presiding_judge_id" x-model="presiding" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in judges" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн 1</label>
                <select name="member_judge_1_id" x-model="m1" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in availableForM1()" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Гишүүн 2</label>
                <select name="member_judge_2_id" x-model="m2" @change="changed()" class="w-full border rounded-md px-3 py-2">
                    <option value="">-- Сонгох --</option>
                    <template x-for="j in availableForM2()" :key="j.id"><option :value="j.id" x-text="j.name"></option></template>
                </select>
            </div>
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-700">Шүүгдэгчийн нэр (таслалаар)</label>
            <textarea name="defendant_names_text" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('defendant_names_text') }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-semibold text-gray-700">Таслан сэргийлэх</label>
                <select name="preventive_measure" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">--</option>
                    @foreach($preventiveMeasures as $pm)
                        <option value="{{ $pm }}" @selected(old('preventive_measure')===$pm)>{{ $pm }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-gray-700">Улсын яллагч</label>
                <select name="prosecutor_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">-</option>
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
                <label class="text-sm font-semibold text-gray-700">Хууль ёсны төлөөлөгчийн өмгөөлөгч (олон сонголт)</label>
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
        <div><label class="text-sm font-semibold text-gray-700">Тэмдэглэл</label>
            <textarea name="note" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">{{ old('note') }}</textarea>
        </div>
        <div x-data="hearingUX()" x-init="init()" class="p-3 rounded-lg border bg-gray-50 text-sm">
            <div>Товч: <span x-text="startText"></span> | <span x-text="duration"></span> мин</div>
            <template x-if="conflictMessage"><div class="mt-2 text-red-700" x-text="conflictMessage"></div></template>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-5 py-2.5 rounded-md bg-blue-900 text-white hover:bg-blue-800">Бүртгэх</button>
            <a href="{{ route('court_clerk.hearings.index') }}" class="px-5 py-2.5 rounded-md bg-gray-100 border hover:bg-gray-200">Буцах</a>
        </div>
    </form>
</div>
<script>
function judgePanelExclude({judges,presiding,m1,m2}){return{judges,presiding:presiding?String(presiding):'',m1:m1?String(m1):'',m2:m2?String(m2):'',init(){this.changed()},changed(){if(this.m1&&this.m1===this.presiding)this.m1='';if(this.m2&&this.m2===this.presiding)this.m2='';if(this.m1&&this.m2&&this.m1===this.m2)this.m2=''},availableForM1(){return this.judges.filter(j=>{const id=String(j.id);return id===this.m1||(id!==this.presiding&&id!==this.m2)})},availableForM2(){return this.judges.filter(j=>{const id=String(j.id);return id===this.m2||(id!==this.presiding&&id!==this.m1)})}}}
function hearingUX(){return{date:'',hour:'',minute:'',courtroom:'',presiding:'',m1:'',m2:'',duration:30,startText:'-',conflictMessage:'',init(){['hearing_date','hour','minute','courtroom','presiding_judge_id','member_judge_1_id','member_judge_2_id'].forEach(n=>{const el=document.querySelector(`[name="${n}"]`);if(el)el.addEventListener('change',()=>{this.read();this.compute();this.checkConflict()})});this.read();this.compute();this.checkConflict()},read(){this.date=document.querySelector('[name="hearing_date"]')?.value||'';this.hour=document.querySelector('[name="hour"]')?.value||'';this.minute=document.querySelector('[name="minute"]')?.value||'';this.courtroom=document.querySelector('[name="courtroom"]')?.value||'';this.presiding=document.querySelector('[name="presiding_judge_id"]')?.value||'';this.m1=document.querySelector('[name="member_judge_1_id"]')?.value||'';this.m2=document.querySelector('[name="member_judge_2_id"]')?.value||''},compute(){const j=[this.presiding,this.m1,this.m2].filter(Boolean);this.duration=new Set(j).size>=3?60:30;if(!this.date||this.hour===''||this.minute===''){this.startText='-';return}const pad=n=>String(n).padStart(2,'0');this.startText=`${this.date} ${pad(this.hour)}:${pad(this.minute)}`},async checkConflict(){this.conflictMessage='';if(!this.date||this.hour===''||this.minute===''||!this.courtroom||!this.presiding)return;const p={hearing_date:this.date,hour:Number(this.hour),minute:Number(this.minute),courtroom:this.courtroom,presiding_judge_id:Number(this.presiding),member_judge_1_id:this.m1?Number(this.m1):null,member_judge_2_id:this.m2?Number(this.m2):null};const r=await fetch("{{ route('court_clerk.hearings.checkConflict') }}",{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":"{{ csrf_token() }}"},body:JSON.stringify(p)});const d=await r.json();if(!d.ok)this.conflictMessage=d.message||"Давхцал"}}
</script>
@endsection
