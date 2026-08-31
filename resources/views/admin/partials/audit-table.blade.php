<section class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-heading text-xl font-bold text-emerald-800">Audit Log</h2>
        <p class="text-gray-500 text-sm">Append-only record of all administrative actions.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">Time</th>
                    <th class="px-4 py-3">Actor</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Target</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($auditLogs as $log)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">{{ $log->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->actor?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $log->action_type->value }}</td>
                        <td class="px-4 py-3">{{ $log->target_table ?? '—' }} #{{ $log->target_id ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3">{{ $auditLogs->links() }}</div>
</section>
