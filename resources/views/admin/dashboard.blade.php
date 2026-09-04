@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Tab navigation --}}
    <div class="flex flex-wrap gap-2 mb-8">
        @php
            $tabCounts = [
                'users' => $counts['users'],
                'donations' => $counts['donations'],
                'requests' => $counts['requests'],
                'matches' => $counts['matches'],
                'pickups' => $counts['pickups'],
            ];
        @endphp
        @foreach (['overview' => 'Overview', 'users' => 'Users', 'donations' => 'Donations', 'requests' => 'Requests', 'matches' => 'Matches', 'pickups' => 'Pickups', 'reports' => 'Reports', 'audit' => 'Audit Log'] as $tab => $label)
            <a href="{{ route('admin.dashboard', array_merge(request()->except(['tab', 'page', 'users_page', 'donations_page', 'requests_page', 'matches_page', 'pickups_page', 'audit_page']), ['tab' => $tab])) }}"
               class="px-4 py-2 rounded-full text-sm font-medium transition {{ $activeTab === $tab ? 'bg-emerald-700 text-white' : 'bg-white text-emerald-800 border border-emerald-200 hover:bg-emerald-50' }}">
                {{ $label }}@if (isset($tabCounts[$tab])) ({{ $tabCounts[$tab] }})@endif
            </a>
        @endforeach
    </div>

    @if ($activeTab !== 'reports')
        @include('admin.partials.filters')
    @endif

    @if ($activeTab === 'overview')
        @include('admin.partials.overview')
    @elseif ($activeTab === 'users')
        @include('admin.partials.users-table')
    @elseif ($activeTab === 'donations')
        @include('admin.partials.donations-table')
    @elseif ($activeTab === 'requests')
        @include('admin.partials.requests-table')
    @elseif ($activeTab === 'matches')
        @include('admin.partials.matches-table')
    @elseif ($activeTab === 'pickups')
        @include('admin.partials.pickups-table')
    @elseif ($activeTab === 'reports')
        @include('admin.partials.reports')
    @elseif ($activeTab === 'audit')
        @include('admin.partials.audit-table')
    @endif
@endsection
