@extends('layouts.dashboard')

@section('title', 'Хянах самбар')
@section('header', 'Хянах самбар')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">@include('partials.widgets.today-hearings', ['hearingsToday' => $hearingsToday, 'today' => $today])</div>
    <div class="lg:col-span-1 w-full">@include('partials.widgets.mini-calendar', ['today' => $today, 'hearingsCountByDay' => $hearingsCountByDay ?? [], 'dashboardUrl' => route('secretary.dashboard')])</div>
</div>
@endsection