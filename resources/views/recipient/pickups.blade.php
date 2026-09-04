@extends('layout')

@section('content')

<h1 class="mb-4">Recipient Pickup Scheduling</h1>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<h5 class="mb-3">Ready to schedule</h5>

@forelse ($unscheduledMatches as $match)
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="mb-1">{{ $match->donation->food_name }}</h6>
            <p class="text-muted mb-3">From {{ $match->donation->donor->name }} ·
                {{ $match->quantity_allocated }} {{ $match->donation->unit }} allocated to you</p>

            <form method="POST" action="{{ route('recipient.pickups.schedule') }}" class="row g-3 align-items-end">
                @csrf
                <input type="hidden" name="match_id" value="{{ $match->id }}">
                <div class="col-md-6">
                    <label class="form-label">Date &amp; Time</label>
                    <input type="datetime-local" name="scheduled_at" required
                        min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-success">Schedule Pickup</button>
                </div>
            </form>
        </div>
    </div>
@empty
    <div class="card mb-4">
        <div class="card-body text-muted">No matches waiting to be scheduled right now.</div>
    </div>
@endforelse

<h5 class="mt-5 mb-3">My pickups</h5>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Food Item</th>
                    <th>Donor</th>
                    <th>Address</th>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pickups as $pickup)
                    <tr>
                        <td>{{ $pickup->donation->food_name }}</td>
                        <td>{{ $pickup->donor->name }}</td>
                        <td>{{ $pickup->pickup_address }}</td>
                        <td>{{ $pickup->scheduled_at->format('d M Y, h:i A') }}</td>
                        <td><span class="status-badge {{ $pickup->status->code }}">{{ ucfirst(str_replace('_', ' ', $pickup->status->code)) }}</span></td>
                        <td>
                            @if (in_array($pickup->status->code, ['scheduled', 'confirmed']))
                                <form method="POST" action="{{ route('recipient.pickups.cancel', $pickup->id) }}"
                                    onsubmit="return confirm('Cancel this pickup?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">You haven't scheduled any pickups yet.</td>
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