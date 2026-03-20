<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title','Dashboard')</title>
    <script>
        // Ensure chipSelect exists before Alpine evaluates x-data expressions.
        window.chipSelect = window.chipSelect || function chipSelect(config = {}) {
            return {
                options: Array.isArray(config.options) ? JSON.parse(JSON.stringify(config.options)) : [],
                selected: Array.isArray(config.selected) ? JSON.parse(JSON.stringify(config.selected)) : [],
                single: !!config.single,
                placeholder: config.placeholder || 'Сонгох...',
                nameId: config.nameId || 'ids[]',
                searchKey() {
                    const base = String(this.nameId || 'ids[]').replace(/\[\]$/,'');
                    return base.replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/-+/g,'-').replace(/(^-|-$)/g,'') || 'chipselect';
                },
                searchId() { return 'chipsearch-' + this.searchKey(); },
                searchName() { return 'chipsearch_' + this.searchKey(); },
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
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') forceRender();
                    });
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
                        const name = String((o && o.name) || '').toLowerCase();
                        const id = String((o && o.id) ?? '').toLowerCase();
                        if (digitsOnly) {
                            return id.includes(q);
                        }
                        return name.includes(q) || id.includes(q);
                    });
                },
                isSelected(opt) { return this.selected.some(s => s.id === opt.id); },
                toggle(opt) {
                    if (this.single) {
                        this.selected = [{ id: opt.id, name: opt.name }];
                        this.open = false;
                        this.query = '';
                        this.refreshFiltered();
                        return;
                    }
                    if (this.isSelected(opt)) this.selected = this.selected.filter(s => s.id !== opt.id);
                    else this.selected = [...this.selected, { id: opt.id, name: opt.name }];
                },
                remove(s) { this.selected = this.selected.filter(x => x.id !== s.id); }
            };
        };
    </script>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

@php
    $u = auth()->user();
    $roleKey = optional($u)->getRoleNames()->first();
    $roleLabel = match ($roleKey) {
        'admin' => 'Админ',
        'secretary' => 'Шүүгчийн туслах',
        'lawyer' => 'Өмгөөлөгч',
        'prosecutor' => 'Прокурор',
        'info_desk', 'information', 'info' => 'Мэдээлэл лавлагаа',
        'court_clerk', 'clerk', 'session_secretary', 'hearing_clerk' => 'Шүүх хурлын нарийн бичгийн дарга',
        default => $roleKey ?? 'role',
    };

    $overdueNotesCount = 0;
    $overdueHearings = collect();
    if ($u && method_exists($u, 'hasRole') && $u->hasRole('court_clerk')) {
        $cutoff = \Carbon\Carbon::today()->subDays(3)->endOfDay();
        $overdueQuery = \App\Models\Hearing::query()
            ->where('clerk_id', $u->id)
            ->where('notes_handover_issued', false)
            ->where(function ($q) use ($cutoff) {
                $q->whereDate('hearing_date', '<=', $cutoff->toDateString())
                    ->orWhere('start_at', '<=', $cutoff);
            });

        $overdueNotesCount = (clone $overdueQuery)->count();
        $overdueHearings = (clone $overdueQuery)
            ->select(['id', 'case_no', 'courtroom', 'hearing_date', 'start_at', 'hour', 'minute'])
            ->orderBy('hearing_date')
            ->orderBy('start_at')
            ->orderBy('hour')
            ->orderBy('minute')
            ->limit(10)
            ->get();
    }

    $todayRoleHearingsCount = 0;
    $todayRoleHearingsUrl = null;
    if ($u && method_exists($u, 'hasRole')) {
        $todayDate = \Carbon\Carbon::today();
        if ($u->hasRole('judge')) {
            $todayRoleHearingsCount = \App\Models\Hearing::query()
                ->whereDate('start_at', $todayDate)
                ->whereHas('judges', fn ($q) => $q->where('users.id', $u->id))
                ->count();
            $todayRoleHearingsUrl = route('judge.hearings.index');
        } elseif ($u->hasRole('prosecutor')) {
            $todayRoleHearingsCount = \App\Models\Hearing::query()
                ->whereDate('start_at', $todayDate)
                ->where(function ($q) use ($u) {
                    $q->where('prosecutor_id', $u->id)
                        ->orWhereJsonContains('prosecutor_ids', $u->id);
                })
                ->count();
            $todayRoleHearingsUrl = route('prosecutor.hearings.index');
        } elseif ($u->hasRole('lawyer')) {
            $todayRoleHearingsCount = \App\Models\Hearing::query()
                ->whereDate('start_at', $todayDate)
                ->where(function ($q) use ($u) {
                    $q->whereJsonContains('defendant_lawyers_text', $u->name)
                        ->orWhereJsonContains('victim_lawyers_text', $u->name)
                        ->orWhereJsonContains('victim_legal_rep_lawyers_text', $u->name)
                        ->orWhereJsonContains('civil_plaintiff_lawyers', $u->name)
                        ->orWhereJsonContains('civil_defendant_lawyers', $u->name);
                })
                ->count();
            $todayRoleHearingsUrl = route('lawyer.hearings.index');
        }
    }

    $notificationCount = $overdueNotesCount + $todayRoleHearingsCount;
@endphp

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<div class="min-h-screen flex">

    {{-- Sidebar --}}
    <aside class="w-64 hidden lg:flex flex-col bg-slate-800 text-white shadow-xl">
        @php
            $roleLogo = asset('images/logo.png');
        @endphp
        <div class="px-5 py-5 border-b border-slate-700/50 flex items-center gap-3 shrink-0">
            <img src="{{ $roleLogo }}" class="w-12 h-12 rounded-xl object-cover ring-2 ring-white/10 shrink-0" alt="Logo">
            <div class="min-w-0">
                <div class="font-semibold text-xs leading-tight text-white">Шүүх Хуралдааны зар товлох дотоод удирдлагын систем</div>
            </div>
        </div>
        @include('partials.sidebar')
    </aside>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-14 shrink-0 flex items-center justify-between px-4 sm:px-6 bg-white border-b border-slate-200/80 shadow-sm">
            <h1 class="text-base font-semibold text-slate-800 truncate">
                @yield('header','Хянах самбар')
            </h1>
            <div class="flex items-center gap-2">
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                    <button type="button"
                            @click="open = !open"
                            class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border {{ $notificationCount > 0 ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-slate-200 bg-white text-slate-400 hover:bg-slate-50' }} transition-colors"
                            title="Мэдэгдэл">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                        </svg>
                        @if($notificationCount > 0)
                            <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-amber-600 text-white text-[11px] leading-[18px] font-bold text-center">
                                {{ $notificationCount > 99 ? '99+' : $notificationCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="open" x-transition.origin.top.right @click.away="open = false"
                         class="absolute right-0 mt-2 w-[360px] max-w-[90vw] rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden z-50">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div class="text-sm font-semibold text-slate-800">Мэдэгдэл</div>
                            <div class="text-xs text-slate-500">{{ $notificationCount }} мэдэгдэл</div>
                        </div>

                        @if($notificationCount <= 0)
                            <div class="px-4 py-4 text-sm text-slate-500">Мэдэгдэл байхгүй.</div>
                        @else
                            <div class="max-h-[320px] overflow-auto divide-y divide-slate-100">
                                @if($todayRoleHearingsCount > 0 && $todayRoleHearingsUrl)
                                    <a href="{{ $todayRoleHearingsUrl }}"
                                       class="block px-4 py-3 hover:bg-slate-50 transition-colors bg-blue-50/50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-slate-800">
                                                    Өнөөдрийн хурал
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5">
                                                    Таны оролцох {{ $todayRoleHearingsCount }} хурал байна.
                                                </div>
                                            </div>
                                            <span class="shrink-0 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-full px-2 py-0.5">
                                                Өнөөдөр
                                            </span>
                                        </div>
                                    </a>
                                @endif

                                @foreach($overdueHearings as $h)
                                    @php
                                        $d = $h->hearing_date ?: $h->start_at;
                                        $dateStr = $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '—';
                                        $timeStr = '—';
                                        if (!empty($h->start_at)) {
                                            $timeStr = \Carbon\Carbon::parse($h->start_at)->format('H:i');
                                        } elseif ($h->hour !== null && $h->minute !== null) {
                                            $timeStr = sprintf('%02d:%02d', $h->hour, $h->minute);
                                        }
                                    @endphp
                                    <a href="{{ route('court_clerk.notes.index', ['hearing_date' => $dateStr]) }}"
                                       class="block px-4 py-3 hover:bg-slate-50 transition-colors">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-slate-800 truncate">
                                                    {{ $h->case_no ?: '—' }}
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5">
                                                    {{ $dateStr }} · {{ $timeStr }} · {{ $h->courtroom ?: '—' }}
                                                </div>
                                            </div>
                                            <span class="shrink-0 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5">
                                                Хүлээлцээгүй
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                            @if($overdueNotesCount > 0)
                                <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                    <a href="{{ route('court_clerk.notes.index') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">
                                        Бүгдийг харах
                                    </a>
                                    <a href="{{ route('court_clerk.notes.index') }}" class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">
                                        Тэмдэглэл хүлээлцэх
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                @if($roleLabel)
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-medium">
                        {{ $roleLabel }}
                    </span>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-700 transition-colors">
                        Гарах
                    </button>
                </form>
            </div>
        </header>

        @if($overdueNotesCount > 0 && session('show_overdue_toast'))
            <div class="fixed top-4 right-4 z-50 max-w-sm"
                 x-data="{ show: true }"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-show="show"
                 x-transition.opacity.duration.200ms>
                <a href="{{ route('court_clerk.notes.index') }}"
                   class="relative block rounded-2xl border border-amber-200 bg-amber-50/95 backdrop-blur px-4 py-3 shadow-lg hover:bg-amber-50 transition-colors">
                    <button type="button"
                            class="absolute top-2 right-2 p-1 rounded-md text-amber-800/70 hover:text-amber-900 hover:bg-amber-100"
                            @click.prevent="show = false"
                            aria-label="Хаах"
                            title="Хаах">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 shrink-0">
                            <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="min-w-0 pr-6">
                            <div class="text-sm font-semibold text-amber-900">Анхааруулга</div>
                            <div class="text-sm text-amber-900/90 mt-0.5">
                                Хурал болсноос хойш 3 хоног өнгөрсөн ч тэмдэглэл хүлээлцээгүй
                                <span class="font-semibold">{{ $overdueNotesCount }}</span>
                                хурал байна.
                            </div>
                            <div class="text-xs text-amber-800/80 mt-1">“Тэмдэглэл хүлээлцэх” хэсэг рүү орох</div>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        <main class="flex-1 p-4 sm:p-6 overflow-auto">
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8 min-h-0">
                @yield('content')
            </div>
        </main>
    </div>
</div>
<script>
    // If Alpine started before all x-data blocks were ready (or was interrupted),
    // re-scan the DOM on common lifecycle events. This mirrors the "tab switch fixes it" symptom.
    const reInitAlpine = () => {
        if (!window.Alpine || typeof window.Alpine.initTree !== 'function') return;
        window.Alpine.initTree(document.body);
    };
    window.addEventListener('load', () => setTimeout(reInitAlpine, 0));
    window.addEventListener('pageshow', () => setTimeout(reInitAlpine, 0));
    window.addEventListener('focus', () => setTimeout(reInitAlpine, 0));
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') setTimeout(reInitAlpine, 0);
    });
</script>
</body>
</html>
