@extends('layouts.app')

@section('title', 'Exit Cashier POS')
@section('page-title', 'Exit Gate Cashier & Settlement POS')

@section('content')
<div class="row g-4">
    <!-- Ticket Search & Billing Engine -->
    <div class="col-lg-7">
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-upc-scan me-2"></i>Scan or Enter Parking Ticket / Plate Number</h6>

            <form action="{{ route('gate.exit') }}" method="GET" class="mb-4">
                <input type="hidden" name="location_id" value="{{ $selectedLocation->id }}">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search_term" class="form-control font-monospace fw-bold text-uppercase" placeholder="Scan Barcode or Type Plate (e.g. TKT-20261015-0001 or B 1024 XYZ)" value="{{ request('search_term') }}" required autofocus>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Lookup</button>
                </div>
            </form>

            @if($activeTicket && $calculation)
            <!-- Active Ticket Detail & Charge Breakdown -->
            <div class="border rounded-3 p-4 bg-light">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                    <div>
                        <span class="badge bg-primary font-monospace fs-6 px-3 py-2">{{ $activeTicket->ticket_number }}</span>
                        <span class="badge bg-dark font-monospace fs-6 px-3 py-2 ms-2">{{ $activeTicket->plate_number }}</span>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $activeTicket->ticket_type === 'member' ? 'bg-info' : 'bg-secondary' }} px-3 py-2 text-uppercase">
                            {{ $activeTicket->ticket_type }} Session
                        </span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Vehicle Type</small>
                        <strong class="text-dark"><i class="bi {{ $activeTicket->vehicleType?->icon }} me-1"></i>{{ $activeTicket->vehicleType?->name }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Parking Bay / Zone</small>
                        <strong class="text-dark">{{ $activeTicket->zone?->name ?? 'Free Flow' }} {{ $activeTicket->slot ? '('.$activeTicket->slot->slot_number.')' : '' }}</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Entry Timestamp</small>
                        <span class="fw-semibold">{{ $activeTicket->entry_time->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Current Exit Timestamp</small>
                        <span class="fw-semibold text-primary">{{ now()->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>

                <!-- Duration Gauge -->
                @php
                    $duration = $calculation['duration_minutes'];
                    $hours = floor($duration / 60);
                    $mins = $duration % 60;
                @endphp
                <div class="p-3 bg-white rounded-3 border mb-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small text-muted text-uppercase fw-bold">Total Duration Elapsed</div>
                        <div class="fs-4 fw-bold text-dark"><i class="bi bi-clock me-1 text-primary"></i>{{ $hours }} Hours {{ $mins }} Minutes</div>
                    </div>
                    <div>
                        @if($calculation['is_grace'])
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                            <i class="bi bi-gift me-1"></i>Grace Period (Free Drop-off)
                        </span>
                        @elseif($calculation['is_member'])
                        <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2">
                            <i class="bi bi-person-check me-1"></i>Active Member (Complimentary)
                        </span>
                        @else
                        <span class="badge bg-secondary px-3 py-2">
                            {{ $calculation['billable_hours'] }} Billable Hours
                        </span>
                        @endif
                    </div>
                </div>

                <!-- Price Calculation Breakdown -->
                <div class="p-3 bg-white rounded-3 border mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Parking Subtotal:</span>
                        <strong class="font-monospace">${{ number_format($calculation['subtotal'], 2) }}</strong>
                    </div>
                    @if($calculation['penalty'] > 0)
                    <div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Lost Ticket / Penalty Fee:</span>
                        <strong class="font-monospace">+${{ number_format($calculation['penalty'], 2) }}</strong>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">VAT / Municipal Tax (10%):</span>
                        <strong class="font-monospace">${{ number_format($calculation['tax'], 2) }}</strong>
                    </div>
                    @if($activeTicket->discount_amount > 0)
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Discount Applied:</span>
                        <strong class="font-monospace">-${{ number_format($activeTicket->discount_amount, 2) }}</strong>
                    </div>
                    @endif
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold text-dark">Total Amount Due:</span>
                        <span class="fs-3 fw-bold text-success font-monospace">${{ number_format($calculation['total'], 2) }}</span>
                    </div>
                </div>

                <!-- Settlement Form -->
                <form action="{{ route('gate.payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $activeTicket->id }}">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Exit Gate Barrier <span class="text-danger">*</span></label>
                            <select name="exit_gate_id" class="form-select" required>
                                @foreach($exitGates as $gate)
                                <option value="{{ $gate->id }}">{{ $gate->name }} ({{ $gate->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" required onchange="handlePaymentMethodChange(this.value)">
                                <option value="cash" selected>Cash Payment (Cashier)</option>
                                <option value="qris_ewallet">QRIS / E-Wallet (Scan & Go)</option>
                                <option value="credit_card">Credit Card (Visa/Mastercard)</option>
                                <option value="debit_card">Debit Card / ATM</option>
                                @if($calculation['is_member'])
                                <option value="member_card">Prepaid Member Pass</option>
                                @endif
                                <option value="complimentary">Complimentary / Management Waiver</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tender Cash Amount & Quick Buttons -->
                    <div id="cash-tender-section" class="mb-4">
                        <label class="form-label small fw-semibold text-secondary">Tender Amount ($)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light fw-bold">$</span>
                            <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control form-control-lg font-monospace fw-bold" value="{{ $calculation['total'] }}" min="0" oninput="calculateChange({{ $calculation['total'] }})">
                        </div>
                        <div class="d-flex gap-2 mb-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTender({{ $calculation['total'] }})">Exact (${{ number_format($calculation['total'], 2) }})</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTender(10)">$10.00</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTender(20)">$20.00</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTender(50)">$50.00</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setTender(100)">$100.00</button>
                        </div>
                        <div class="p-2 bg-white rounded border d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Change to return:</span>
                            <strong class="font-monospace text-primary fs-5" id="change-display">$0.00</strong>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg fw-bold py-3 shadow-sm">
                            <i class="bi bi-check2-circle me-2"></i>Complete Settlement & Raise Exit Barrier
                        </button>
                    </div>
                </form>
            </div>
            @else
            <!-- Placeholder when no search -->
            <div class="text-center py-5 border rounded-3 bg-light text-muted">
                <i class="bi bi-upc-scan fs-1 d-block mb-3 text-secondary"></i>
                <h6>Ready to Scan Ticket</h6>
                <p class="small mb-0">Use the search box above to lookup ticket barcode number or vehicle plate.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Right Column: Quick Lost Ticket Shortcut & Recent Exits -->
    <div class="col-lg-5">
        <!-- Lost Ticket Card -->
        <div class="card-custom p-4 mb-4 border-warning bg-warning bg-opacity-10">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-warning text-dark rounded-circle fs-3">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Lost Ticket Incident?</h6>
                    <p class="small text-secondary mb-2">Driver lost the entrance ticket slip. Launch affidavit verification wizard.</p>
                    <a href="{{ route('gate.lost_ticket') }}" class="btn btn-sm btn-warning fw-semibold">
                        <i class="bi bi-arrow-right-circle me-1"></i>Open Lost Ticket Recovery
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Completed Transactions -->
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Exit Settlements</h6>
            <div class="list-group list-group-flush">
                @forelse($recentExits as $exit)
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-dark font-monospace">{{ $exit->plate_number }}</span>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            {{ $exit->ticket_number }} • ${{ number_format($exit->total_amount, 2) }} ({{ strtoupper($exit->payment_method) }})
                        </div>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ $exit->exit_time ? $exit->exit_time->format('H:i') : '--' }}</small>
                        <span class="badge bg-success ms-1"><i class="bi bi-check"></i> Cleared</span>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3 small">No completed check-outs today yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Payment Receipt Settled Modal -->
@if(session('payment_settled'))
@php $payment = session('payment_settled'); @endphp
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content shadow border-0">
            <div class="modal-body p-4 text-center">
                <div class="d-inline-flex p-3 bg-success-subtle text-success rounded-circle mb-3">
                    <i class="bi bi-check-circle-fill fs-2"></i>
                </div>
                <h5 class="fw-bold mb-1">Exit Barrier Released!</h5>
                <p class="text-muted small mb-3">Payment confirmed. Thank you for parking with us.</p>

                <!-- Receipt Slip -->
                <div class="border rounded-3 p-3 bg-light text-start font-monospace small mb-3">
                    <div class="text-center fw-bold border-bottom pb-2 mb-2">
                        <div>{{ $payment->ticket?->location?->name ?? 'Parking Management System' }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">OFFICIAL PARKING RECEIPT / TAX INVOICE</div>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Invoice:</span>
                        <strong>{{ $payment->invoice_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Plate:</span>
                        <strong>{{ $payment->ticket?->plate_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Method:</span>
                        <span class="text-uppercase">{{ $payment->payment_method }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Time:</span>
                        <span>{{ $payment->paid_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Paid:</span>
                        <strong class="text-success fs-6">${{ number_format($payment->amount_paid, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Change Given:</span>
                        <span>${{ number_format($payment->change_given, 2) }}</span>
                    </div>

                    <div class="text-center text-muted border-top pt-2 mt-2" style="font-size: 0.68rem;">
                        Have a safe journey! Visit again.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary flex-fill" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print Receipt
                    </button>
                    <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    function setTender(amount) {
        document.getElementById('amount_paid').value = amount.toFixed(2);
        calculateChange({{ $calculation['total'] ?? 0 }});
    }

    function calculateChange(totalDue) {
        const tendered = parseFloat(document.getElementById('amount_paid').value) || 0;
        const change = Math.max(0, tendered - totalDue);
        document.getElementById('change-display').textContent = '$' + change.toFixed(2);
    }

    function handlePaymentMethodChange(method) {
        const tenderSection = document.getElementById('cash-tender-section');
        if (tenderSection) {
            tenderSection.style.display = (method === 'cash') ? 'block' : 'none';
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const modalEl = document.getElementById('receiptModal');
        if (modalEl) {
            const receiptModal = new bootstrap.Modal(modalEl);
            receiptModal.show();
        }
    });
</script>
@endsection
