@extends('layouts.dashboard')

@section('header', 'Хянах самбар')

@section('content')
@include('partials.dashboards.role-hearings-dashboard', [
    'hearingsToday' => $hearingsToday,
    'today' => $today,
    'hearingsCountByDay' => $hearingsCountByDay ?? [],
    'decisionOptions' => $decisionOptions ?? [],
    'decisionCounts' => $decisionCounts ?? [],
    'decisionFilterBaseUrl' => route('lawyer.hearings.index'),
    'dashboardUrl' => route('lawyer.dashboard'),
])
@endsection

