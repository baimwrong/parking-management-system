@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Operations Dashboard')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h4 class="fw-bold mb-1">{{ $location->name ?? 'Parking Facility' }}</h4>
        <p class="text-muted small mb-0"><i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $location->address ?? 'Location address' }} • Code: <span class="badge bg-secondary">{{ $location->code ?? 'LOC' }}</span></p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <form method="GET" action="{{ route('dashboard') }}" class="d-inline-flex align-items-center gap-2">
            <label class="small text-muted fw-semibold">Switch Site:</label>
            <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}" {{ $loc->id == $location->id ? 'selected' : '' }}>
                    {{ $loc->name }} ({{ $loc->code }})
                </option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<!-- Primary KPI Metrics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
            <div class="kpi-icon primary">
                <i class="bi bi-pie-chart-fill"></i>
            </div>
            <div>
                <div class="kpi-value">{{ $occupancyRate }}%</div>
                <div class="kpi-label">Facility Occupancy</div>
                <small class="text-muted">{{ $parkedCount }} / {{ $totalCapacity }} Bays</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
            <div class="kpi-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="kpi-value">{{ $availableCapacity }}</div>
                <div class="kpi-label">Available Slots</div>
                <small class="text-success fw-semibold"><i class="bi bi-arrow-down-short"></i>Ready for Parking</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
            <div class="kpi-icon warning">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div>
                <div class="kpi-value">${{ number_format($todayRevenue, 2) }}</div>
                <div class="kpi-label">Today's Revenue</div>
                <small class="text-muted"><i class="bi bi-receipt me-1"></i>Settled Transactions</small>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="kpi-card shadow-sm">
            <div class="kpi-icon purple">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div>
                <div class="kpi-value">{{ $todayEntries }} <span class="fs-6 fw-normal text-muted">In /</span> {{ $todayExits }} <span class="fs-6 fw-normal text-muted">Out</span></div>
                <div class="kpi-label">Today's Traffic</div>
                <small class="text-muted">Active: {{ $parkedCount }} inside</small>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Types & Zone Breakdown Row -->
<div class="row g-4 mb-4">
    <!-- Vehicle Category Breakdown -->
    <div class="col-lg-6">
        <div class="card-custom h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-car-front-fill me-2 text-primary"></i>Live Vehicles Parked by Category</h6>
                <span class="badge bg-light text-dark border">{{ $parkedCount }} Active</span>
            </div>
            <div class="row g-3">
                @foreach($vehicleBreakdown as $vb)
                <div class="col-sm-6">
                    <div class="p-3 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-3 text-secondary"><i class="bi {{ $vb['type']->icon }}"></i></div>
                            <div>
                                <div class="fw-bold fs-5">{{ $vb['count'] }}</div>
                                <small class="text-muted">{{ $vb['type']->name }}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                ${{ number_format($vb['type']->hourly_rate, 2) }}/h
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Parking Zones Occupancy -->
    <div class="col-lg-6">
        <div class="card-custom h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-layers-fill me-2 text-info"></i>Zone Capacity & Floor Status</h6>
                <a href="{{ route('slots.index') }}" class="btn btn-sm btn-outline-primary">Manage Slots</a>
            </div>
            <div class="d-flex flex-column gap-3">
                @foreach($location->zones as $zone)
                @php
                    $occupied = $zone->slots()->where('status', 'occupied')->count();
                    $total = max(1, $zone->total_slots);
                    $pct = min(100, round(($occupied / $total) * 100));
                    $color = $pct > 85 ? 'danger' : ($pct > 60 ? 'warning' : 'success');
                @endphp
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <span class="fw-semibold">{{ $zone->name }}</span>
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $zone->code }}</span>
                        </div>
                        <small class="text-muted fw-semibold">{{ $occupied }} / {{ $zone->total_slots }} Slots ({{ $pct }}%)</small>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Terminals & Live Gates Status -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card-custom h-100 p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-hdd-network-fill me-2 text-success"></i>Gate Barrier Terminals</h6>
            <div class="d-flex flex-column gap-2">
                @foreach($gates as $gate)
                <div class="p-3 rounded-3 border d-flex align-items-center justify-content-between {{ $gate->barrier_status === 'open' ? 'bg-warning-subtle' : 'bg-light' }}">
                    <div>
                        <div class="fw-bold small">{{ $gate->name }}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ $gate->code }} • IP: {{ $gate->ip_address ?? '127.0.0.1' }}
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $gate->barrier_status === 'open' ? 'bg-warning text-dark' : 'bg-success' }}">
                            <i class="bi {{ $gate->barrier_status === 'open' ? 'bi-shield-exclamation' : 'bi-shield-check' }} me-1"></i>
                            {{ strtoupper($gate->barrier_status) }}
                        </span>
                        <div class="mt-1">
                            <span class="badge bg-secondary" style="font-size: 0.65rem;">
                                {{ strtoupper($gate->gate_type) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small text-muted"><i class="bi bi-shield-lock me-1"></i>Active Violations:</span>
                    <span class="badge {{ $activeViolations > 0 ? 'bg-danger' : 'bg-success' }}">{{ $activeViolations }} Cases</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="small text-muted"><i class="bi bi-person-badge me-1"></i>Active Members:</span>
                    <span class="badge bg-info text-dark">{{ $activeMembers }} Passes</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Active Check-ins -->
    <div class="col-lg-8">
        <div class="card-custom h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Inbound Check-ins</h6>
                <a href="{{ route('tickets.index') }}" class="btn btn-sm btn-link text-decoration-none">View All Tickets <i class="bi bi-arrow-right"></i></a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket #</th>
                            <th>Vehicle</th>
                            <th>Zone / Bay</th>
                            <th>Gate</th>
                            <th>Entry Time</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket->id) }}" class="fw-bold text-decoration-none font-monospace">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-dark font-monospace px-2 py-1">{{ $ticket->plate_number }}</span>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $ticket->vehicleType?->name }}</div>
                            </td>
                            <td>
                                {{ $ticket->zone?->name ?? 'Free Flow' }}
                                @if($ticket->slot)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $ticket->slot->slot_number }}</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-secondary">{{ $ticket->entryGate?->code ?? 'Gate 1' }}</small>
                            </td>
                            <td>
                                <div>{{ $ticket->entry_time->format('H:i:s') }}</div>
                                <small class="text-muted">{{ $ticket->entry_time->diffForHumans() }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $ticket->status === 'parked' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('tickets.print', $ticket->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print Ticket">
                                    <i class="bi bi-printer"></i>
                                </a>
                                @if($ticket->status === 'parked')
                                <a href="{{ route('gate.exit', ['search_term' => $ticket->ticket_number]) }}" class="btn btn-sm btn-primary" title="Check-out">
                                    <i class="bi bi-cash"></i> Pay
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No recent tickets recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
