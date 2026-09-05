@php
    $metrics = $overviewMetrics ?? ($platformStat?->metrics ?? []);
@endphp

<section>
    <h2 class="font-heading text-2xl font-bold text-emerald-800 mb-2">Platform Statistics</h2>
    <p class="text-gray-500 text-sm mb-6">
        @if ($dashboardFilters->hasFilters())
            Filtered statistics
        @else
            Cached stats
            @if ($platformStat)
                · last updated
                {{ $platformStat->created_at ? \Carbon\Carbon::parse($platformStat->created_at)->diffForHumans() : '—' }}
                · period {{ \Carbon\Carbon::parse($platformStat->period_start)->format('M j, H:i') }} –
                {{ \Carbon\Carbon::parse($platformStat->period_end)->format('M j, H:i') }}
            @endif
        @endif
    </p>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-3xl font-bold text-emerald-700">{{ $metrics['users']['total'] ?? $counts['users'] }}</p>
            <p class="text-gray-600 text-sm mt-1">Total Users</p>
            <p class="text-xs text-gray-400 mt-2">{{ $metrics['users']['active'] ?? '—' }} active ·
                {{ $metrics['users']['inactive'] ?? '—' }} inactive</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-3xl font-bold text-emerald-700">{{ $metrics['donations']['total'] ?? $counts['donations'] }}
            </p>
            <p class="text-gray-600 text-sm mt-1">Donations</p>
            <p class="text-xs text-gray-400 mt-2">{{ $metrics['donations']['completed'] ?? '—' }} completed</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-3xl font-bold text-emerald-700">{{ $metrics['matches']['total'] ?? $counts['matches'] }}</p>
            <p class="text-gray-600 text-sm mt-1">Matches</p>
            <p class="text-xs text-gray-400 mt-2">{{ $metrics['matches']['completed'] ?? '—' }} completed</p>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <p class="text-3xl font-bold text-emerald-700">{{ $metrics['pickups']['total'] ?? $counts['pickups'] }}</p>
            <p class="text-gray-600 text-sm mt-1">Pickups</p>
            <p class="text-xs text-gray-400 mt-2">{{ $metrics['pickups']['completed'] ?? '—' }} completed</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-3">Users by Role</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li>Donors: <span class="font-medium">{{ $metrics['users']['donors'] ?? '—' }}</span></li>
                <li>Recipients: <span class="font-medium">{{ $metrics['users']['recipients'] ?? '—' }}</span></li>
                <li>Admins: <span class="font-medium">{{ $metrics['users']['admins'] ?? '—' }}</span></li>
            </ul>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
            <h3 class="font-semibold text-gray-800 mb-3">Impact</h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li>Meals redistributed: <span
                        class="font-medium">{{ $metrics['impact']['meals_redistributed'] ?? '—' }}</span></li>
                <li>Food saved (kg): <span class="font-medium">{{ $metrics['impact']['food_kg_saved'] ?? '—' }}</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-5 border border-gray-100">
        <h3 class="font-semibold text-gray-800 mb-4">Quick Browse</h3>
        <div class="flex flex-wrap gap-3">
            @foreach (['users', 'donations', 'requests', 'matches', 'pickups', 'reports'] as $tab)
                <a href="{{ route('admin.dashboard', ['tab' => $tab]) }}"
                    class="text-emerald-700 hover:underline capitalize">{{ $tab }}</a>
            @endforeach
        </div>
    </div>
</section>
