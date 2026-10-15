@extends('layouts.app')

@section('title', 'Violations & Enforcement')
@section('page-title', 'Parking Enforcement & Violations')

@section('content')
<div class="card-custom p-4 mb-4">
    <div class="row g-3 align-items-center justify-content-between mb-4">
        <div class="col-md-7">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('violations.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    All Records ({{ \App\Models\ParkingViolation::count() }})
                </a>
                <a href="{{ route('violations.index', ['status' => 'issued']) }}" class="btn btn-sm {{ $status === 'issued' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="bi bi-exclamation-circle me-1"></i>Issued Pending
                </a>
                <a href="{{ route('violations.index', ['status' => 'clamped']) }}" class="btn btn-sm {{ $status === 'clamped' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-lock-fill me-1"></i>Wheel Clamped ({{ \App\Models\ParkingViolation::where('status', 'clamped')->count() }})
                </a>
                <a href="{{ route('violations.index', ['status' => 'paid']) }}" class="btn btn-sm {{ $status === 'paid' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="bi bi-check-circle me-1"></i>Settled
                </a>
            </div>
        </div>

        <div class="col-md-5 text-md-end">
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#newViolationModal">
                <i class="bi bi-shield-plus me-1"></i>Issue Violation Notice
            </button>
        </div>
    </div>

    <!-- Violations Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>Notice ID</th>
                    <th>Vehicle Plate</th>
                    <th>Violation Reason</th>
                    <th>Location / Slot</th>
                    <th>Fine Amount</th>
                    <th>Issued At</th>
                    <th>Marshall</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $v)
                <tr>
                    <td class="font-monospace fw-bold">#VIO-{{ sprintf('%04d', $v->id) }}</td>
                    <td>
                        <span class="badge bg-dark font-monospace px-2 py-1">{{ $v->plate_number }}</span>
                    </td>
                    <td>
                        <strong class="text-dark">{{ ucwords(str_replace('_', ' ', $v->violation_type)) }}</strong>
                        <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $v->notes }}</small>
                    </td>
                    <td>
                        {{ $v->location?->name ?? 'Metropolis' }}
                        @if($v->slot)
                        <span class="badge bg-secondary-subtle text-secondary">{{ $v->slot->slot_number }}</span>
                        @endif
                    </td>
                    <td>
                        <strong class="font-monospace text-danger">${{ number_format($v->fine_amount, 2) }}</strong>
                    </td>
                    <td>
                        <div>{{ $v->created_at->format('M d, H:i') }}</div>
                        <small class="text-muted">{{ $v->created_at->diffForHumans() }}</small>
                    </td>
                    <td>
                        <small class="text-secondary">{{ $v->officer?->name ?? 'System' }}</small>
                    </td>
                    <td>
                        <span class="badge {{ $v->status === 'clamped' ? 'bg-danger' : ($v->status === 'paid' ? 'bg-success' : 'bg-warning text-dark') }}">
                            {{ strtoupper($v->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            @if($v->status !== 'paid')
                            <form action="{{ route('violations.status', $v->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="paid">
                                <button type="submit" class="btn btn-outline-success" title="Mark Fine Paid">
                                    <i class="bi bi-check-lg"></i> Collect
                                </button>
                            </form>
                            @endif

                            @if($v->status === 'clamped')
                            <form action="{{ route('violations.status', $v->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="issued">
                                <input type="hidden" name="reason" value="Clamp released by officer">
                                <button type="submit" class="btn btn-outline-warning" title="Release Wheel Clamp">
                                    <i class="bi bi-unlock"></i> Unclamp
                                </button>
                            </form>
                            @elseif($v->status === 'issued')
                            <form action="{{ route('violations.status', $v->id) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="status" value="clamped">
                                <input type="hidden" name="reason" value="Vehicle clamped for obstruction/refusal">
                                <button type="submit" class="btn btn-outline-danger" title="Attach Wheel Clamp">
                                    <i class="bi bi-lock"></i> Clamp
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-shield-check fs-1 d-block mb-2 text-success"></i>
                        No parking violations on record. Facility in compliance.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $violations->links() }}
    </div>
</div>

<!-- New Violation Notice Modal -->
<div class="modal fade" id="newViolationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="{{ route('violations.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h6 class="modal-title fw-bold"><i class="bi bi-shield-slash me-2"></i>Issue Violation Notice</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Facility Site <span class="text-danger">*</span></label>
                        <select name="parking_location_id" class="form-select" required>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Vehicle License Plate <span class="text-danger">*</span></label>
                            <input type="text" name="plate_number" class="form-control font-monospace fw-bold text-uppercase" placeholder="e.g. B 9911 WRG" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Fine Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="5" name="fine_amount" class="form-control font-monospace" value="100.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Violation Infraction Type <span class="text-danger">*</span></label>
                        <select name="violation_type" class="form-select" required>
                            <option value="unauthorized_slot">Parked in Reserved / Unauthorized VIP Slot</option>
                            <option value="overstay">Overstay Exceeded Allowed Grace Limit</option>
                            <option value="blocking_aisle">Double Parked / Blocking Driveway Aisle</option>
                            <option value="clamped">Immediate Wheel Immobilization / Clamped</option>
                            <option value="no_ticket">Unauthorized Bypass / No Entry Record</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Slot Location (If parked in a bay)</label>
                        <select name="parking_slot_id" class="form-select">
                            <option value="">-- No Specific Bay / Aisle Roadway --</option>
                            @foreach($availableSlots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->slot_number }} ({{ $slot->zone?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Patrol Officer Notes & Observations <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Describe vehicle position, tire chalk marks, warnings issued..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold">Issue Notice & Log</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
