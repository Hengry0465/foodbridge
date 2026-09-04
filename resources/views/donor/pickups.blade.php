@extends('layout')

@section('content')

<h1 class="mb-4">Donor Pickup Management</h1>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h2>{{ $stats['scheduled'] }}</h2>
            <h6>Scheduled</h6>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h2>{{ $stats['confirmed'] }}</h2>
            <h6>Confirmed</h6>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h2>{{ $stats['completed'] }}</h2>
            <h6>Completed</h6>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center">
            <h2>{{ $stats['cancelled'] }}</h2>
            <h6>Cancelled</h6>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Food Item</th>
                    <th>Recipient</th>
                    <th>Address</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pickups as $pickup)
                    <tr>
                        <td>{{ $pickup->donation->food_name }}</td>
                        <td>{{ $pickup->recipient->name }}</td>
                        <td>{{ $pickup->pickup_address }}</td>
                        <td>{{ $pickup->scheduled_at->format('d M Y, h:i A') }}</td>
                        <td><span class="status-badge {{ $pickup->status->code }}">{{ ucfirst(str_replace('_', ' ', $pickup->status->code)) }}</span></td>
                        <td>
                            @if ($pickup->status->code === 'scheduled')
                                <form method="POST" action="{{ route('donor.pickups.updateStatus', $pickup->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="confirmed">
                                    <button class="btn btn-success btn-sm">Confirm</button>
                                </form>
                            @elseif ($pickup->status->code === 'confirmed')
                                <form method="POST" action="{{ route('donor.pickups.updateStatus', $pickup->id) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="status" value="completed">
                                    <button class="btn btn-primary btn-sm">Complete</button>
                                </form>
                            @endif

                            @if (in_array($pickup->status->code, ['scheduled', 'confirmed']))
                                <form method="POST" action="{{ route('donor.pickups.updateStatus', $pickup->id) }}" class="d-inline"
                                    onsubmit="return confirm('Cancel this pickup?');">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="btn btn-danger btn-sm">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No pickups yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($pickups->hasPages())
            {{ $pickups->links() }}
        @endif
    </div>
</div>

@endsection