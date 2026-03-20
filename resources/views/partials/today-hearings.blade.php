@php
    use Carbon\Carbon;
@endphp

<div class="bg-white/10 border border-white/10 rounded-xl p-6 shadow-lg">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold">Өнөөдрийн хурал</h3>
        <span class="text-xs opacity-80">{{ $today->format('Y-m-d') }}</span>
    </div>

    @if($hearingsToday->isEmpty())
        <div class="text-sm opacity-80">Өнөөдөр зарлагдсан хурал алга.</div>
    @else
        <div class="space-y-3">
            @foreach($hearingsToday as $h)
                <div class="p-4 rounded-lg bg-black/20 border border-white/10">
                    <div class="flex justify-between gap-4">
                        <div class="font-semibold">
                            {{ Carbon::parse($h->starts_at)->format('H:i') }} — {{ $h->title }}
                        </div>
                        <div class="text-xs opacity-80">
                            {{ $h->courtroom ?? 'Танхим: -' }}
                        </div>
                    </div>
                    <div class="text-xs opacity-80 mt-1">
                        Хэрэг: {{ $h->case_no ?? '-' }} | Статус: {{ $h->status }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
