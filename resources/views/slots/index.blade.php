@extends('layouts.app')

@section('title', 'Parking Slots')
@section('page-title', 'Parking Slots & Facility Layout')

@section('content')
<div class="row g-4 mb-4">
    <!-- Zone Capacity Summary Cards -->
    @foreach($zones as $z)
    <div class="col-sm-6 col-lg-3">
        <div class="card-custom p-3 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="badge bg-secondary-subtle text-secondary">{{ $z->code }}</span>
                <span class="badge bg-primary">{{ $z->floor_level }}</span>
            </div>
            <h6 class="fw-bold mb-1 text-truncate">{{ $z->name }}</h6>
            <div class="d-flex justify-content-between text-muted small mt-2">
                <span>Free: <strong class="text-success">{{ $z->available_slots_count }}</strong></span>
                <span>Occupied: <strong class="text-danger">{{ $z->occupied_slots_count }}</strong></span>
                <span>Total: <strong>{{ $z->total_slots_count }}</strong></span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card-custom p-4 mb-4">
    <!-- Filter and Action Bar -->
    <div class="row g-3 align-items-center justify-content-between mb-4">
        <div class="col-md-8">
            <form method="GET" action="{{ route('slots.index') }}" class="row g-2 align-items-center">
                <input type="hidden" name="location_id" value="{{ $selectedLocation->id }}">

                <div class="col-sm-4">
                    <select name="zone_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Zones</option>
                        @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ $selectedZoneId == $z->id ? 'selected' : '' }}>{{ $z->name }} ({{ $z->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-sm-4">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="available" {{ $statusFilter == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="occupied" {{ $statusFilter == 'occupied' ? 'selected' : '' }}>Occupied</option>
                        <option value="reserved" {{ $statusFilter == 'reserved' ? 'selected' : '' }}>Reserved</option>
                        <option value="maintenance" {{ $statusFilter == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>

                <div class="col-sm-4">
                    <select name="vehicle_type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Vehicle Types</option>
                        <option value="car" {{ $typeFilter == 'car' ? 'selected' : '' }}>Cars</option>
                        <option value="motorcycle" {{ $typeFilter == 'motorcycle' ? 'selected' : '' }}>Motorcycles</option>
                        <option value="ev" {{ $typeFilter == 'ev' ? 'selected' : '' }}>EV Charging</option>
                    </select>
                </div>
            </form>
        </div>

        @if(Auth::check() && Auth::user()->isManager())
        <div class="col-md-4 text-md-end">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newSlotModal">
                <i class="bi bi-plus-lg me-1"></i>Add Parking Bay
            </button>
        </div>
        @endif
    </div>

    <!-- Status Legend -->
    <div class="d-flex gap-3 mb-4 flex-wrap small text-muted">
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-success p-1 rounded-circle" style="width: 10px; height: 10px;"> </span> Available
        </div>
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-danger p-1 rounded-circle" style="width: 10px; height: 10px;"> </span> Occupied
        </div>
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-warning p-1 rounded-circle" style="width: 10px; height: 10px;"> </span> Reserved
        </div>
        <div class="d-flex align-items-center gap-1">
            <span class="badge bg-secondary p-1 rounded-circle" style="width: 10px; height: 10px;"> </span> Maintenance
        </div>
    </div>

    <!-- Slot Visual Grid -->
    <div class="slot-grid">
        @forelse($slots as $slot)
        <div class="slot-box {{ $slot->status }}" onclick="openSlotModal('{{ $slot->id }}', '{{ $slot->slot_number }}', '{{ $slot->status }}', '{{ $slot->vehicle_type }}', '{{ $slot->activeTicket?->plate_number }}')" title="{{ $slot->slot_number }} ({{ ucfirst($slot->status) }})">
            <div style="font-size: 0.7rem;" class="text-uppercase text-truncate">{{ $slot->zone?->code }}</div>
            <div class="fs-6 fw-bold my-1 font-monospace">{{ $slot->slot_number }}</div>
            <div style="font-size: 0.68rem;">
                @if($slot->status === 'occupied')
                <span class="badge bg-dark font-monospace" style="font-size: 0.6rem;">{{ $slot->activeTicket?->plate_number ?? 'OCCUPIED' }}</span>
                @else
                <span class="text-capitalize">{{ $slot->status }}</span>
                @endif
            </div>
        </div>
        @empty
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No parking slots matched your filters.
        </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $slots->links() }}
    </div>
</div>

<!-- Quick Slot Status Change Modal -->
<div class="modal fade" id="slotStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow border-0">
            <form id="slotStatusForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="slotModalTitle">Manage Slot</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="slotParkedInfo" class="alert alert-danger py-2 small d-none mb-3">
                        <i class="bi bi-car-front-fill me-1"></i>Currently parked by vehicle: <strong id="slotParkedPlate"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Slot Status</label>
                        <select name="status" id="slotStatusSelect" class="form-select">
                            <option value="available">Available (Open for Parking)</option>
                            <option value="occupied">Occupied</option>
                            <option value="reserved">Reserved (VIP / Tenants)</option>
                            <option value="maintenance">Maintenance / Out of Order</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Slot Modal -->
@if(Auth::check() && Auth::user()->isManager())
<div class="modal fade" id="newSlotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="{{ route('slots.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Add Parking Bay</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Target Zone <span class="text-danger">*</span></label>
                        <select name="parking_zone_id" class="form-select" required>
                            @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->name }} ({{ $z->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Slot Number / Code <span class="text-danger">*</span></label>
                        <input type="text" name="slot_number" class="form-control font-monospace text-uppercase" placeholder="e.g. B1-A25" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Vehicle Category <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="car">Car / Sedan</option>
                            <option value="motorcycle">Motorcycle</option>
                            <option value="suv">SUV / MPV</option>
                            <option value="ev">Electric Vehicle (EV Charger)</option>
                            <option value="truck">Commercial Truck</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Near Elevator Lobby 2">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Bay</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    function openSlotModal(id, number, status, vtype, parkedPlate) {
        document.getElementById('slotModalTitle').textContent = `Manage Bay ${number}`;
        document.getElementById('slotStatusSelect').value = status;
        document.getElementById('slotStatusForm').action = `/slots/${id}/status`;

        const infoEl = document.getElementById('slotParkedInfo');
        const plateEl = document.getElementById('slotParkedPlate');

        if (parkedPlate && parkedPlate !== '') {
            plateEl.textContent = parkedPlate;
            infoEl.classList.remove('d-none');
        } else {
            infoEl.classList.add('d-none');
        }

        const modal = new bootstrap.Modal(document.getElementById('slotStatusModal'));
        modal.show();
    }
</script>
@endsection
