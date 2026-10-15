@extends('layouts.app')

@section('title', 'Lost Ticket Wizard')
@section('page-title', 'Lost Ticket Resolution & Affidavit Wizard')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="p-3 bg-danger-subtle text-danger rounded-circle fs-3">
                    <i class="bi bi-file-earmark-break-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Lost Ticket Clearance Protocol</h5>
                    <p class="text-muted small mb-0">Follow standard operating procedure for vehicles without valid entry barcode ticket.</p>
                </div>
            </div>

            <form action="{{ route('gate.process_lost_ticket') }}" method="POST">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Parking Facility <span class="text-danger">*</span></label>
                        <select name="parking_location_id" class="form-select" required>
                            @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Exit Gate Barrier <span class="text-danger">*</span></label>
                        <select name="exit_gate_id" class="form-select" required>
                            @foreach($gates as $g)
                            <option value="{{ $g->id }}">{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Vehicle License Plate <span class="text-danger">*</span></label>
                        <input type="text" name="plate_number" class="form-control form-control-lg font-monospace fw-bold text-uppercase" placeholder="e.g. B 9999 XYZ" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Vehicle Category <span class="text-danger">*</span></label>
                        <select name="vehicle_type_id" id="vtype_select" class="form-select form-select-lg" required onchange="updatePenalty(this)">
                            @foreach($vehicleTypes as $vt)
                            <option value="{{ $vt->id }}" data-penalty="{{ $vt->lost_ticket_penalty }}">
                                {{ $vt->name }} (Penalty: ${{ number_format($vt->lost_ticket_penalty, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Estimated Arrival / Entry Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="estimated_entry_time" class="form-control" value="{{ now()->subHours(2)->format('Y-m-d\TH:i') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Driver ID Number (Passport / DL) <span class="text-danger">*</span></label>
                        <input type="text" name="driver_id_number" class="form-control" placeholder="ID Card or Driver License No" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Driver Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="driver_phone" class="form-control" placeholder="+1-555-0123" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash" selected>Cash (Cashier)</option>
                            <option value="qris_ewallet">QRIS / E-Wallet</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                        </select>
                    </div>
                </div>

                <!-- Fee Calculation Box -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Standard Lost Ticket Penalty:</span>
                        <strong class="font-monospace" id="display-penalty">$50.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">VAT Tax (10%):</span>
                        <strong class="font-monospace" id="display-tax">$5.00</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold text-dark">Total Lost Ticket Fine Due:</span>
                        <span class="fs-4 fw-bold text-danger font-monospace" id="display-total">$55.00</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Amount Tendered ($) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="amount_paid" id="lost_amount_paid" class="form-control form-control-lg font-monospace fw-bold" value="55.00" min="0" required>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-danger btn-lg flex-fill fw-bold py-3 shadow-sm">
                        <i class="bi bi-shield-check me-2"></i>Affirm Affidavit, Collect Fine & Raise Barrier
                    </button>
                    <a href="{{ route('gate.exit') }}" class="btn btn-outline-secondary btn-lg py-3 px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function updatePenalty(selectEl) {
        const selected = selectEl.options[selectEl.selectedIndex];
        const penalty = parseFloat(selected.getAttribute('data-penalty')) || 50;
        const tax = penalty * 0.10;
        const total = penalty + tax;

        document.getElementById('display-penalty').textContent = '$' + penalty.toFixed(2);
        document.getElementById('display-tax').textContent = '$' + tax.toFixed(2);
        document.getElementById('display-total').textContent = '$' + total.toFixed(2);
        document.getElementById('lost_amount_paid').value = total.toFixed(2);
    }

    document.addEventListener("DOMContentLoaded", function () {
        const el = document.getElementById('vtype_select');
        if (el) updatePenalty(el);
    });
</script>
@endsection
