<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-3">
        @include('partials.widgets.decision-stats', [
            'today' => $today,
            'decisionOptions' => $decisionOptions ?? [],
            'decisionCounts' => $decisionCounts ?? [],
            'decisionFilterBaseUrl' => $decisionFilterBaseUrl ?? null,
        ])
    </div>
    <div class="lg:col-span-2">
        @include('partials.widgets.today-hearings', ['hearingsToday' => $hearingsToday, 'today' => $today])
    </div>
    <div class="lg:col-span-1">
        @include('partials.widgets.mini-calendar', [
            'today' => $today,
            'hearingsCountByDay' => $hearingsCountByDay ?? [],
            'dashboardUrl' => $dashboardUrl,
        ])
    </div>
</div>
