@extends('layouts.dashboard')
@section('title','Хянах самбар')
@section('header','Хянах самбар')

@section('content')
<div class="space-y-6">
    @include('partials.widgets.notes-handover-stats', [
        'monthTotalHearings' => $monthTotalHearings ?? 0,
        'monthIssuedHearings' => $monthIssuedHearings ?? 0,
        'monthPendingHearings' => $monthPendingHearings ?? 0,
        'notesHandoverFilterBaseUrl' => route('court_clerk.notes.index', [
            'hearing_date_from' => $today->copy()->startOfMonth()->toDateString(),
            'hearing_date_to' => $today->copy()->endOfMonth()->toDateString(),
        ]),
    ])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">@include('partials.widgets.today-hearings', ['hearingsToday' => $hearingsToday, 'today' => $today])</div>
        <div class="lg:col-span-1">@include('partials.widgets.mini-calendar', ['today' => $today, 'hearingsCountByDay' => $hearingsCountByDay ?? [], 'dashboardUrl' => route('court_clerk.dashboard')])</div>
    </div>
</div>
@endsection
