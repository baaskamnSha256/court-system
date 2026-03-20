@extends('layouts.dashboard')

@section('header','Хянах самбар')

@section('content')
@include('partials.dashboards.role-hearings-dashboard', [
    'hearingsToday' => $hearingsToday,
    'today' => $today,
    'hearingsCountByDay' => $hearingsCountByDay ?? [],
    'decisionOptions' => $decisionOptions ?? [],
    'decisionCounts' => $decisionCounts ?? [],
    'decisionFilterBaseUrl' => route('judge.hearings.index'),
    'dashboardUrl' => route('judge.dashboard'),
])
@endsection
