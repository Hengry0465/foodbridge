@php
    $statusOptions = match ($activeTab) {
        'donations' => $donationStatuses,
        'requests' => $requestStatuses,
        'matches' => $matchStatuses,
        'pickups' => $pickupStatuses,
        default => [],
    };

    $showSearch = in_array($activeTab, ['users', 'donations', 'requests', 'audit'], true);
    $showStatus = in_array($activeTab, ['donations', 'requests', 'matches', 'pickups'], true);
    $showCategory = in_array($activeTab, ['donations', 'requests'], true);
    $showRegion = in_array($activeTab, ['overview', 'users', 'donations', 'requests', 'matches', 'pickups'], true);
    $showRole = in_array($activeTab, ['users', 'overview'], true);
    $showActive = $activeTab === 'users';
    $showActionType = $activeTab === 'audit';
    $showDateRange = in_array($activeTab, ['overview', 'users', 'donations', 'requests', 'matches', 'pickups', 'audit'], true);
@endphp

<div class="bg-white rounded-xl shadow border border-gray-100 p-5 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h3 class="font-semibold text-gray-800">Filters</h3>
            @if ($dashboardFilters->hasFilters())
                <p class="text-xs text-emerald-700 mt-0.5">Filters active</p>
            @endif
        </div>
        @if ($dashboardFilters->hasFilters())
            <a href="{{ route('admin.dashboard', ['tab' => $activeTab]) }}"
               class="text-sm text-gray-600 hover:text-emerald-800 underline">
                Clear all
            </a>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" class="grid md:grid-cols-4 gap-4">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        @if ($activeTab === 'reports')
            <input type="hidden" name="type" value="{{ $reportType->value }}">
        @endif

        @if ($showSearch)
            <div class="{{ $activeTab === 'audit' ? 'md:col-span-2' : '' }}">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Search
                    @if ($activeTab === 'users')
                        (name, username, email)
                    @elseif (in_array($activeTab, ['donations', 'requests'], true))
                        (user name)
                    @elseif ($activeTab === 'audit')
                        (actor name)
                    @endif
                </label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Type to search..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        @endif

        @if ($showDateRange)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
        @endif

        @if ($showStatus)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>
                            {{ ucfirst($statusOption->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($showCategory)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    @foreach ($foodCategories as $category)
                        <option value="{{ $category->value }}" @selected(request('category') === $category->value)>
                            {{ ucfirst($category->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($showRegion)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <select name="region" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All regions</option>
                    @foreach ($foodRegions as $region)
                        <option value="{{ $region->value }}" @selected(request('region') === $region->value)>
                            {{ ucwords(str_replace('_', ' ', $region->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($showRole)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All roles</option>
                    @foreach ($userRoles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>
                            {{ ucfirst($role->value) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($showActive)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                <select name="is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All</option>
                    <option value="1" @selected(request('is_active') === '1')>Active</option>
                    <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                </select>
            </div>
        @endif

        @if ($showActionType)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Action Type</label>
                <select name="action_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All actions</option>
                    @foreach ($auditActionTypes as $actionType)
                        <option value="{{ $actionType->value }}" @selected(request('action_type') === $actionType->value)>
                            {{ $actionType->value }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="md:col-span-4 flex gap-3">
            <button type="submit" class="bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-emerald-800">
                Apply Filters
            </button>
        </div>
    </form>
</div>
