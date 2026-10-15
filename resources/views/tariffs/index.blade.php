@extends('layouts.app')

@section('title', 'Tariffs & Rates')
@section('page-title', 'Tariff Configuration & Pricing Engine')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h5 class="fw-bold mb-1">Pricing Rules & Tariffs</h5>
        <p class="text-muted small mb-0">Define hourly calculation rules, progressive fees, lost ticket penalties, and grace periods.</p>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTariffModal">
            <i class="bi bi-plus-lg me-1"></i>New Tariff Rule
        </button>
    </div>
</div>

<div class="row g-4">
    @foreach($tariffs as $t)
    <div class="col-md-6 col-xl-4">
        <div class="card-custom h-100 p-4 border-top border-4 border-primary position-relative">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 bg-light rounded-circle fs-4 text-primary">
                        <i class="bi {{ $t->vehicleType?->icon ?? 'bi-car-front' }}"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">{{ $t->name }}</h6>
                        <small class="text-muted">{{ $t->vehicleType?->name }}</small>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase" style="font-size: 0.68rem;">
                    {{ $t->tariff_type }}
                </span>
            </div>

            <div class="p-3 bg-light rounded-3 mb-3 font-monospace small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Grace Period:</span>
                    <strong>{{ $t->grace_period_minutes }} Mins (Free)</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">1st {{ $t->initial_duration_minutes }} Mins:</span>
                    <strong class="text-primary">${{ number_format($t->initial_rate, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subsequent Rate:</span>
                    <strong>${{ number_format($t->subsequent_rate_per_hour, 2) }} / hour</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Max 24h Daily Cap:</span>
                    <strong class="text-success">${{ number_format($t->max_daily_cap, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Overnight Fee:</span>
                    <span>+${{ number_format($t->overnight_surcharge, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span>Lost Ticket Penalty:</span>
                    <strong>${{ number_format($t->lost_ticket_fee, 2) }}</strong>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-auto">
                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $t->location?->code }}</small>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openEditTariffModal('{{ $t->id }}', '{{ $t->name }}', '{{ $t->initial_duration_minutes }}', '{{ $t->initial_rate }}', '{{ $t->subsequent_rate_per_hour }}', '{{ $t->max_daily_cap }}', '{{ $t->overnight_surcharge }}', '{{ $t->lost_ticket_fee }}', '{{ $t->grace_period_minutes }}')">
                    <i class="bi bi-pencil me-1"></i>Adjust Rates
                </button>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Edit Tariff Modal -->
<div class="modal fade" id="editTariffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form id="editTariffForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="editTariffTitle">Adjust Tariff Rates</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tariff Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Grace Period (Mins)</label>
                            <input type="number" name="grace_period_minutes" id="edit_grace" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Initial Minutes</label>
                            <input type="number" name="initial_duration_minutes" id="edit_initial_min" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Initial Rate ($)</label>
                            <input type="number" step="0.5" name="initial_rate" id="edit_initial_rate" class="form-control font-monospace" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Subsequent Rate / Hr ($)</label>
                            <input type="number" step="0.5" name="subsequent_rate_per_hour" id="edit_subsequent_rate" class="form-control font-monospace" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Max 24h Daily Cap ($)</label>
                            <input type="number" step="1" name="max_daily_cap" id="edit_daily_cap" class="form-control font-monospace" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Overnight Surcharge ($)</label>
                            <input type="number" step="1" name="overnight_surcharge" id="edit_overnight" class="form-control font-monospace">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Lost Ticket Flat Penalty ($)</label>
                        <input type="number" step="1" name="lost_ticket_fee" id="edit_lost_fee" class="form-control font-monospace" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Tariff Modal -->
<div class="modal fade" id="newTariffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <form action="{{ route('tariffs.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Create New Tariff Policy</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Facility Location <span class="text-danger">*</span></label>
                        <select name="parking_location_id" class="form-select" required>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Vehicle Category <span class="text-danger">*</span></label>
                        <select name="vehicle_type_id" class="form-select" required>
                            @foreach($vehicleTypes as $vt)
                            <option value="{{ $vt->id }}">{{ $vt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Tariff Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Peak Hours Express Rate" required>
                    </div>

                    <input type="hidden" name="tariff_type" value="progressive">

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Initial Minutes</label>
                            <input type="number" name="initial_duration_minutes" class="form-control" value="60" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Initial Rate ($)</label>
                            <input type="number" step="0.5" name="initial_rate" class="form-control" value="5.00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Subsequent Rate / Hr ($)</label>
                            <input type="number" step="0.5" name="subsequent_rate_per_hour" class="form-control" value="3.00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">24h Daily Cap ($)</label>
                            <input type="number" step="1" name="max_daily_cap" class="form-control" value="40.00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Grace Period (Mins)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="15" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Lost Ticket Fee ($)</label>
                            <input type="number" step="1" name="lost_ticket_fee" class="form-control" value="50.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Tariff</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function openEditTariffModal(id, name, initMin, initRate, subRate, dailyCap, overnight, lostFee, grace) {
        document.getElementById('editTariffTitle').textContent = `Adjust Rates: ${name}`;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_initial_min').value = initMin;
        document.getElementById('edit_initial_rate').value = initRate;
        document.getElementById('edit_subsequent_rate').value = subRate;
        document.getElementById('edit_daily_cap').value = dailyCap;
        document.getElementById('edit_overnight').value = overnight;
        document.getElementById('edit_lost_fee').value = lostFee;
        document.getElementById('edit_grace').value = grace;

        document.getElementById('editTariffForm').action = `/tariffs/${id}`;

        const modal = new bootstrap.Modal(document.getElementById('editTariffModal'));
        modal.show();
    }
</script>
@endsection
