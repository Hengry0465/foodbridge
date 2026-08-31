<section>
    <div class="bg-white rounded-xl shadow border border-gray-100 p-6 mb-6">
        <h2 class="font-heading text-xl font-bold text-emerald-800 mb-4">Generate Filtered Report</h2>
        <p class="text-gray-500 text-sm mb-6">Filters stack dynamically using the Decorator pattern (date range, food category, region, status, etc.).</p>

        <form method="GET" action="{{ route('admin.dashboard') }}" class="grid md:grid-cols-3 gap-4">
            <input type="hidden" name="tab" value="reports">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach ($reportTypes as $type)
                        <option value="{{ $type->value }}" @selected($reportType === $type)>{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" name="from" value="{{ request('from') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" name="to" value="{{ request('to') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All statuses</option>
                    @php
                        $reportStatusOptions = match ($reportType) {
                            \App\Enums\ReportType::Donations => $donationStatuses,
                            \App\Enums\ReportType::Requests => $requestStatuses,
                            \App\Enums\ReportType::Matches => $matchStatuses,
                            \App\Enums\ReportType::Pickups => $pickupStatuses,
                            default => [],
                        };
                    @endphp
                    @foreach ($reportStatusOptions as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected(request('status') === $statusOption->value)>
                            {{ ucfirst($statusOption->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All categories</option>
                    @foreach ($foodCategories as $category)
                        <option value="{{ $category->value }}" @selected(request('category') === $category->value)>{{ ucfirst($category->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <select name="region" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All regions</option>
                    @foreach ($foodRegions as $region)
                        <option value="{{ $region->value }}" @selected(request('region') === $region->value)>
                            {{ ucwords(str_replace('_', ' ', $region->value)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Role (users only)</label>
                <select name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    <option value="">All roles</option>
                    @foreach ($userRoles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ ucfirst($role->value) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3 flex gap-3">
                <button type="submit" class="bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-emerald-800">
                    Apply Filters
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.reports.export') }}" class="mt-4 pt-4 border-t border-gray-100">
            @csrf
            <input type="hidden" name="type" value="{{ $reportType->value }}">
            <input type="hidden" name="from" value="{{ request('from') }}">
            <input type="hidden" name="to" value="{{ request('to') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="hidden" name="category" value="{{ request('category') }}">
            <input type="hidden" name="region" value="{{ request('region') }}">
            <input type="hidden" name="role" value="{{ request('role') }}">
            <button type="submit" class="bg-gray-800 text-white px-5 py-2.5 rounded-lg font-medium hover:bg-gray-900">
                Export as PDF
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-semibold text-gray-800">Results — {{ ucfirst(str_replace('_', ' ', $reportType->value)) }}</h3>
            <span class="text-sm text-gray-500">{{ $reportRows->total() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        @foreach ($reportColumns as $column)
                            <th class="px-4 py-3">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportRows as $row)
                        <tr class="border-t border-gray-100">
                            @foreach ($reportColumns as $column)
                                <td class="px-4 py-3">
                                    @php
                                        $value = data_get($row, $column);
                                        if (is_array($value)) {
                                            $value = json_encode($value);
                                        } elseif (is_object($value) && enum_exists($value::class)) {
                                            $value = $value->value;
                                        }
                                    @endphp
                                    {{ $value }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($reportColumns) }}" class="px-4 py-8 text-center text-gray-500">No records match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $reportRows->appends(request()->query())->links() }}</div>
    </div>
</section>
