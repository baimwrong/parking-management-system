@extends('layouts.app')

@section('title', 'Parking Tickets')
@section('page-title', 'Parking Tickets & Sessions')

@section('content')
<div class="card-custom p-4 mb-4">
    <!-- Filter and Search Header -->
    <div class="row g-3 align-items-center justify-content-between mb-4">
        <div class="col-md-7">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('tickets.index', ['status' => 'parked']) }}" class="btn btn-sm {{ $status === 'parked' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-car-front-fill me-1"></i>Active Parked ({{ \App\Models\ParkingTicket::where('status', 'parked')->count() }})
                </a>
                <a href="{{ route('tickets.index', ['status' => 'completed']) }}" class="btn btn-sm {{ $status === 'completed' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi bi-check2-circle me-1"></i>Settled Exits
                </a>
                <a href="{{ route('tickets.index', ['status' => 'lost']) }}" class="btn btn-sm {{ $status === 'lost' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-exclamation-octagon me-1"></i>Lost Tickets
                </a>
                <a href="{{ route('tickets.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    All Records
                </a>
            </div>
        </div>

        <div class="col-md-5">
            <form method="GET" action="{{ route('tickets.index') }}">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search ticket #, plate or barcode..." value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                    @if(request('search'))
                    <a href="{{ route('tickets.index', ['status' => $status]) }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>Ticket #</th>
                    <th>Plate Number</th>
                    <th>Vehicle Type</th>
                    <th>Zone / Bay</th>
                    <th>Entry Time</th>
                    <th>Elapsed / Exit</th>
                    <th>Fee / Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $t)
                <tr>
                    <td>
                        <a href="{{ route('tickets.show', $t->id) }}" class="fw-bold font-monospace text-decoration-none">
                            {{ $t->ticket_number }}
                        </a>
                        <div class="text-muted" style="font-size: 0.72rem;">*{{ $t->barcode_hash }}*</div>
                    </td>
                    <td>
                        <span class="badge bg-dark font-monospace px-2 py-1">{{ $t->plate_number }}</span>
                        @if($t->ticket_type === 'member')
                        <span class="badge bg-info text-dark" style="font-size: 0.65rem;">MEMBER</span>
                        @endif
                    </td>
                    <td>
                        <i class="bi {{ $t->vehicleType?->icon }} me-1 text-primary"></i>{{ $t->vehicleType?->name }}
                    </td>
                    <td>
                        {{ $t->zone?->name ?? 'Free Flow' }}
                        @if($t->slot)
                        <span class="badge bg-secondary-subtle text-secondary">{{ $t->slot->slot_number }}</span>
                        @endif
                    </td>
                    <td>
                        <div>{{ $t->entry_time->format('M d, H:i') }}</div>
                        <small class="text-muted">{{ $t->entryGate?->code }}</small>
                    </td>
                    <td>
                        @if($t->status === 'parked')
                            <span class="text-primary fw-semibold">{{ floor($t->duration_minutes / 60) }}h {{ $t->duration_minutes % 60 }}m</span>
                            <small class="text-muted d-block">Parked inside</small>
                        @elseif($t->exit_time)
                            <div>{{ $t->exit_time->format('M d, H:i') }}</div>
                            <small class="text-muted">Total: {{ floor($t->duration_minutes / 60) }}h {{ $t->duration_minutes % 60 }}m</small>
                        @else
                            --
                        @endif
                    </td>
                    <td>
                        <strong class="font-monospace text-success fs-6">${{ number_format($t->total_amount, 2) }}</strong>
                        @if($t->payment_method)
                        <div class="text-muted text-uppercase" style="font-size: 0.7rem;">{{ $t->payment_method }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $t->status === 'parked' ? 'bg-success' : ($t->status === 'completed' ? 'bg-secondary' : 'bg-danger') }}">
                            {{ ucfirst($t->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('tickets.show', $t->id) }}" class="btn btn-outline-secondary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('tickets.print', $t->id) }}" target="_blank" class="btn btn-outline-secondary" title="Print Barcode Ticket">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if($t->status === 'parked')
                            <a href="{{ route('gate.exit', ['search_term' => $t->ticket_number]) }}" class="btn btn-primary" title="Checkout">
                                <i class="bi bi-cash"></i> Checkout
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>
                        No parking tickets found for the selected filter.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
