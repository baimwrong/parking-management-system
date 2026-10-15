@extends('layouts.app')

@section('title', 'Ticket Details')
@section('page-title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="row g-4 justify-content-center">
    <div class="col-lg-8">
        <div class="card-custom p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h5 class="fw-bold mb-1 font-monospace">{{ $ticket->ticket_number }}</h5>
                    <span class="badge bg-dark font-monospace fs-6 px-3 py-1">{{ $ticket->plate_number }}</span>
                    <span class="badge {{ $ticket->status === 'parked' ? 'bg-success' : 'bg-secondary' }} px-3 py-1 ms-2">
                        {{ strtoupper($ticket->status) }}
                    </span>
                </div>
                <div class="text-end">
                    <a href="{{ route('tickets.print', $ticket->id) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-printer me-1"></i>Print Ticket Slip
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Facility Location</label>
                    <div class="fw-bold">{{ $ticket->location?->name }}</div>
                    <small class="text-muted">{{ $ticket->location?->address }}</small>
                </div>

                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Vehicle Category</label>
                    <div class="fw-bold"><i class="bi {{ $ticket->vehicleType?->icon }} text-primary me-1"></i>{{ $ticket->vehicleType?->name }}</div>
                    <small class="text-muted">Type: {{ strtoupper($ticket->ticket_type) }}</small>
                </div>

                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Zone & Assigned Slot</label>
                    <div class="fw-bold">{{ $ticket->zone?->name ?? 'Free Flow' }}</div>
                    <small class="text-muted">Bay Code: {{ $ticket->slot?->slot_number ?? 'Auto-allocated' }}</small>
                </div>

                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Duration Elapsed</label>
                    <div class="fw-bold fs-5 text-primary">{{ floor($ticket->duration_minutes / 60) }}h {{ $ticket->duration_minutes % 60 }}m</div>
                    <small class="text-muted">({{ $ticket->duration_minutes }} Total Minutes)</small>
                </div>

                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Entry Gate & Timestamp</label>
                    <div class="fw-bold">{{ $ticket->entry_time->format('Y-m-d H:i:s') }}</div>
                    <small class="text-muted">Gate: {{ $ticket->entryGate?->name ?? 'Gate 1' }}</small>
                </div>

                <div class="col-sm-6">
                    <label class="small text-muted text-uppercase fw-semibold d-block">Exit Gate & Timestamp</label>
                    <div class="fw-bold">{{ $ticket->exit_time ? $ticket->exit_time->format('Y-m-d H:i:s') : 'Still Parked Inside' }}</div>
                    <small class="text-muted">Gate: {{ $ticket->exitGate?->name ?? 'N/A' }}</small>
                </div>
            </div>

            <!-- Financial Charges Box -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Financial & Tariff Breakdown</h6>
                    @if(Auth::check() && Auth::user()->isManager() && $ticket->status === 'parked')
                    <button type="button" class="btn btn-xs btn-outline-primary" data-bs-toggle="modal" data-bs-target="#discountModal">
                        <i class="bi bi-tag me-1"></i>Apply Discount
                    </button>
                    @endif
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Base Rate & Duration Subtotal:</span>
                    <span class="font-monospace">${{ number_format($ticket->subtotal, 2) }}</span>
                </div>
                @if($ticket->penalty_amount > 0)
                <div class="d-flex justify-content-between mb-2 text-danger">
                    <span>Penalty / Lost Fee:</span>
                    <span class="font-monospace">+${{ number_format($ticket->penalty_amount, 2) }}</span>
                </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">VAT Tax (10%):</span>
                    <span class="font-monospace">${{ number_format($ticket->tax_amount, 2) }}</span>
                </div>
                @if($ticket->discount_amount > 0)
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span>Discount / Voucher Applied:</span>
                    <span class="font-monospace">-${{ number_format($ticket->discount_amount, 2) }}</span>
                </div>
                @endif
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">Total Amount:</span>
                    <span class="fs-4 fw-bold text-success font-monospace">${{ number_format($ticket->total_amount, 2) }}</span>
                </div>
            </div>

            <!-- Payments History -->
            @if($ticket->payments->count() > 0)
            <div class="mb-4">
                <h6 class="fw-bold mb-3">Payment Receipts</h6>
                @foreach($ticket->payments as $payment)
                <div class="p-3 border rounded-3 bg-white mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">{{ $payment->invoice_number }}</div>
                        <small class="text-muted">{{ $payment->paid_at->format('M d, Y H:i') }} • Method: <span class="text-uppercase">{{ $payment->payment_method }}</span></small>
                    </div>
                    <div class="text-end">
                        <strong class="text-success font-monospace fs-5">${{ number_format($payment->amount_paid, 2) }}</strong>
                        <div class="small text-muted">Cashier: {{ $payment->cashier?->name ?? 'System' }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            <div class="d-flex justify-content-between">
                <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back to Tickets
                </a>
                @if($ticket->status === 'parked')
                <a href="{{ route('gate.exit', ['search_term' => $ticket->ticket_number]) }}" class="btn btn-primary px-4 fw-bold">
                    <i class="bi bi-cash me-1"></i>Process Checkout
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Discount Modal -->
@if(Auth::check() && Auth::user()->isManager())
<div class="modal fade" id="discountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow border-0">
            <form action="{{ route('tickets.discount', $ticket->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Apply Management Discount</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Discount Amount ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.5" name="discount_amount" class="form-control" placeholder="e.g. 5.00" max="{{ $ticket->total_amount }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Reason / Authorization <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Tenant validation stamp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Apply Discount</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection
