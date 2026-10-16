@extends('layouts.app')

@section('title', 'Member Passes')
@section('page-title', 'Membership Passes & Subscriptions')

@section('content')
<div class="card-custom p-4 mb-4">
    <div class="row g-3 align-items-center justify-content-between mb-4">
        <div class="col-md-7">
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('members.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    All Passes ({{ \App\Models\MemberPass::count() }})
                </a>
                <a href="{{ route('members.index', ['status' => 'active']) }}" class="btn btn-sm {{ $status === 'active' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="bi bi-check-circle me-1"></i>Active ({{ \App\Models\MemberPass::where('status', 'active')->count() }})
                </a>
                <a href="{{ route('members.index', ['status' => 'expired']) }}" class="btn btn-sm {{ $status === 'expired' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-clock me-1"></i>Expired ({{ \App\Models\MemberPass::where('status', 'expired')->count() }})
                </a>
                <a href="{{ route('members.index', ['status' => 'suspended']) }}" class="btn btn-sm {{ $status === 'suspended' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="bi bi-pause-circle me-1"></i>Suspended
                </a>
            </div>
        </div>

        <div class="col-md-5 d-flex gap-2 justify-content-md-end">
            <form method="GET" action="{{ route('members.index') }}" class="d-flex gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search member, plate, RFID..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            </form>
            @if(Auth::check() && Auth::user()->isManager())
            <button type="button" class="btn btn-sm btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#newMemberModal">
                <i class="bi bi-plus-lg me-1"></i>New Pass
            </button>
            @endif
        </div>
    </div>

    <!-- Member Passes Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
            <thead class="table-light">
                <tr>
                    <th>Pass # / RFID</th>
                    <th>Member Name</th>
                    <th>Plate Number</th>
                    <th>Vehicle Type</th>
                    <th>Plan Type</th>
                    <th>Validity Period</th>
                    <th>Days Left</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $m)
                @php
                    $isExpired = $m->valid_until->isPast();
                    $daysRemaining = $m->days_remaining;
                @endphp
                <tr>
                    <td>
                        <div class="fw-bold font-monospace text-primary">{{ $m->pass_number }}</div>
                        <small class="text-muted font-monospace"><i class="bi bi-upc me-1"></i>{{ $m->rfid_uid ?? 'No RFID Linked' }}</small>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $m->member_name }}</div>
                        <small class="text-muted">{{ $m->phone }}</small>
                    </td>
                    <td>
                        <span class="badge bg-dark font-monospace px-2 py-1">{{ $m->plate_number }}</span>
                    </td>
                    <td>
                        <i class="bi {{ $m->vehicleType?->icon }} me-1 text-primary"></i>{{ $m->vehicleType?->name }}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border text-uppercase">{{ $m->plan_type }}</span>
                        <div class="text-muted" style="font-size: 0.72rem;">${{ number_format($m->price_paid, 2) }}</div>
                    </td>
                    <td>
                        <div>{{ $m->valid_from->format('M d, Y') }} -</div>
                        <strong>{{ $m->valid_until->format('M d, Y') }}</strong>
                    </td>
                    <td>
                        @if($isExpired)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Expired</span>
                        @elseif($daysRemaining <= 7)
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ $daysRemaining }} days left</span>
                        @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $daysRemaining }} days left</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $m->status === 'active' ? 'bg-success' : ($m->status === 'expired' ? 'bg-danger' : 'bg-warning') }}">
                            {{ ucfirst($m->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="openRenewModal('{{ $m->id }}', '{{ $m->pass_number }}', '{{ $m->member_name }}')" title="Renew Pass">
                                <i class="bi bi-arrow-repeat"></i> Renew
                            </button>
                            @if(Auth::check() && Auth::user()->isManager())
                            <form action="{{ route('members.toggle_status', $m->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary" title="Toggle Status">
                                    <i class="bi {{ $m->status === 'active' ? 'bi-pause' : 'bi-play' }}"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-person-badge fs-1 d-block mb-2"></i>
                        No member passes found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $members->links() }}
    </div>
</div>

<!-- Renew Pass Modal -->
<div class="modal fade" id="renewPassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content shadow border-0">
            <form id="renewForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold" id="renewModalTitle">Renew Member Pass</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3" id="renewMemberText">Extending subscription validity.</p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Renewal Duration <span class="text-danger">*</span></label>
                        <select name="duration_months" class="form-select" required onchange="updateRenewPrice(this.value)">
                            <option value="1">1 Month Extension</option>
                            <option value="3">3 Months Extension (Quarterly)</option>
                            <option value="6">6 Months Extension (Semi-Annual)</option>
                            <option value="12" selected>12 Months Extension (Annual)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Amount Collected ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount_paid" id="renew_amount" class="form-control font-monospace fw-bold" value="1200.00" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg me-1"></i>Confirm Renewal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Member Modal -->
@if(Auth::check() && Auth::user()->isManager())
<div class="modal fade" id="newMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0">
            <form action="{{ route('members.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Issue New Member Pass</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Member Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="member_name" class="form-control" placeholder="e.g. Eleanor Vance" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="+1-555-0199" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="member@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">RFID Card UID (Optional)</label>
                            <input type="text" name="rfid_uid" class="form-control font-monospace" placeholder="e.g. E280116060000...">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Vehicle License Plate <span class="text-danger">*</span></label>
                            <input type="text" name="plate_number" class="form-control font-monospace fw-bold text-uppercase" placeholder="e.g. B 1234 ABC" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Vehicle Category <span class="text-danger">*</span></label>
                            <select name="vehicle_type_id" class="form-select" required>
                                @foreach($vehicleTypes as $vt)
                                <option value="{{ $vt->id }}">{{ $vt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Plan Type <span class="text-danger">*</span></label>
                            <select name="plan_type" class="form-select" required>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly (3 Months)</option>
                                <option value="annual" selected>Annual (12 Months)</option>
                                <option value="vip_complimentary">VIP Complimentary</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Price Paid ($)</label>
                            <input type="number" step="0.01" name="price_paid" class="form-control font-monospace" value="1200.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="valid_from" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="valid_until" class="form-control" value="{{ date('Y-m-d', strtotime('+1 year')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Company Tenant, Reserved Bay, etc.">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Membership Pass</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    function openRenewModal(id, passNum, name) {
        document.getElementById('renewModalTitle').textContent = `Renew Pass ${passNum}`;
        document.getElementById('renewMemberText').textContent = `Extending subscription for ${name}.`;
        document.getElementById('renewForm').action = `{{ url('members') }}/${id}/renew`;

        const modal = new bootstrap.Modal(document.getElementById('renewPassModal'));
        modal.show();
    }

    function updateRenewPrice(months) {
        let price = 100;
        if (months == 1) price = 100;
        if (months == 3) price = 280;
        if (months == 6) price = 550;
        if (months == 12) price = 1000;
        document.getElementById('renew_amount').value = price.toFixed(2);
    }
</script>
@endsection
