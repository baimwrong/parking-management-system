@extends('layouts.app')

@section('title', 'Entry Terminal (ANPR)')
@section('page-title', 'Vehicle Entry Terminal & ANPR Camera')

@section('content')
<div class="row g-4">
    <!-- ANPR Camera Simulator & Form -->
    <div class="col-lg-7">
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-camera-video-fill me-2"></i>Automated License Plate Recognition (ANPR) & Gate Control</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-broadcast me-1"></i>Camera Live</span>
            </div>

            <!-- Simulated Camera Feed Window -->
            <div class="position-relative bg-dark rounded-3 overflow-hidden mb-4 text-center d-flex flex-column justify-content-center align-items-center" style="height: 180px; border: 2px dashed #475569;">
                <div class="position-absolute top-0 start-0 m-2 badge bg-danger d-flex align-items-center gap-1">
                    <span class="spinner-grow spinner-grow-sm" role="status"></span> REC • CAM-01 (1080p HD)
                </div>
                <div class="position-absolute top-0 end-0 m-2 text-white-50 font-monospace small">
                    FPS: 30 • LATENCY: 12ms
                </div>

                <div class="p-3 border border-warning rounded-2 bg-black bg-opacity-50 text-warning font-monospace" style="box-shadow: 0 0 15px rgba(234, 179, 8, 0.4);">
                    <div style="font-size: 0.75rem; letter-spacing: 0.1em;" class="text-uppercase mb-1">Optical Character Recognition Frame</div>
                    <div class="fs-4 fw-bold tracking-wider" id="camera-overlay-plate">SCANNING FOR VEHICLE...</div>
                </div>

                <div class="position-absolute bottom-0 start-0 m-2 text-white-50 small">
                    <i class="bi bi-cpu me-1"></i>AI Confidence: <span class="text-success fw-bold">99.4%</span>
                </div>
            </div>

            <form action="{{ route('gate.checkin') }}" method="POST">
                @csrf
                <input type="hidden" name="parking_location_id" value="{{ $selectedLocation->id }}">

                <div class="row g-3 mb-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold text-secondary">Vehicle License Plate Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light font-monospace fw-bold"><i class="bi bi-123 text-muted"></i></span>
                            <input type="text" name="plate_number" id="plate_number" class="form-control form-control-lg font-monospace fw-bold text-uppercase" placeholder="e.g. B 1234 XYZ" required autofocus oninput="document.getElementById('camera-overlay-plate').textContent = this.value.toUpperCase() || 'SCANNING...'">
                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomPlate()" title="Generate Random Plate">
                                <i class="bi bi-shuffle"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-secondary">Entry Gate Terminal <span class="text-danger">*</span></label>
                        <select name="entry_gate_id" class="form-select form-select-lg" required>
                            @foreach($entryGates as $gate)
                            <option value="{{ $gate->id }}">{{ $gate->name }} ({{ $gate->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Vehicle Type Selector -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary mb-2">Vehicle Category <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        @foreach($vehicleTypes as $index => $vt)
                        <div class="col-sm-4">
                            <input type="radio" class="btn-check" name="vehicle_type_id" id="vtype_{{ $vt->id }}" value="{{ $vt->id }}" {{ $index === 1 ? 'checked' : '' }} required>
                            <label class="btn btn-outline-secondary w-100 text-start p-2 d-flex align-items-center gap-2" for="vtype_{{ $vt->id }}">
                                <i class="bi {{ $vt->icon }} fs-4 text-primary"></i>
                                <div>
                                    <div class="fw-bold small">{{ $vt->name }}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Base ${{ number_format($vt->base_rate, 2) }} • ${{ number_format($vt->hourly_rate, 2) }}/h</div>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Optional Slot Allocation -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Preferred Zone</label>
                        <select name="parking_zone_id" id="zone_select" class="form-select" onchange="filterSlotsByZone(this.value)">
                            <option value="">-- Free Flow / Auto Assign --</option>
                            @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->name }} ({{ $z->available_slots }} free)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Designated Bay / Slot (Optional)</label>
                        <select name="parking_slot_id" id="slot_select" class="form-select">
                            <option value="">-- Auto-allocate first available --</option>
                            @foreach($zones as $z)
                                @foreach($z->slots as $s)
                                <option value="{{ $s->id }}" data-zone-id="{{ $z->id }}">{{ $s->slot_number }} ({{ $z->code }})</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                        <i class="bi bi-printer me-2"></i>Dispense Ticket & Raise Barrier Gate
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Recent Vehicles & Manual Override -->
    <div class="col-lg-5">
        <!-- Manual Barrier Override Card -->
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>Manual Barrier Gate Override</h6>
            <form action="{{ route('gate.barrier_override') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <select name="gate_id" class="form-select form-select-sm" required>
                        @foreach($entryGates as $g)
                        <option value="{{ $g->id }}">{{ $g->name }} (Current: {{ strtoupper($g->barrier_status) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for manual override (Required)" required>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="open" class="btn btn-sm btn-success flex-fill">
                        <i class="bi bi-arrow-up-circle me-1"></i>Open Barrier
                    </button>
                    <button type="submit" name="action" value="close" class="btn btn-sm btn-danger flex-fill">
                        <i class="bi bi-arrow-down-circle me-1"></i>Close Barrier
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Parked Vehicles -->
        <div class="card-custom p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Check-in Log</h6>
                <span class="badge bg-light text-dark border">{{ count($recentEntries) }} Recent</span>
            </div>

            <div class="list-group list-group-flush">
                @forelse($recentEntries as $entry)
                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-dark font-monospace">{{ $entry->plate_number }}</span>
                        <small class="text-muted d-block">{{ $entry->vehicleType?->name }} • {{ $entry->ticket_number }}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">{{ $entry->entry_time->format('H:i') }}</small>
                        <a href="{{ route('tickets.print', $entry->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary ms-2" title="Print">
                            <i class="bi bi-printer"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3 small">No check-ins today yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Ticket Issued Success Modal -->
@if(session('ticket_issued'))
@php $issued = session('ticket_issued'); @endphp
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content shadow border-0">
            <div class="modal-body p-4 text-center">
                <div class="d-inline-flex p-3 bg-success-subtle text-success rounded-circle mb-3">
                    <i class="bi bi-check-lg fs-2"></i>
                </div>
                <h5 class="fw-bold mb-1">Barrier Gate Raised!</h5>
                <p class="text-muted small mb-3">Ticket dispensed successfully. Welcome to {{ $issued->location?->name }}.</p>

                <!-- Ticket Preview Card -->
                <div class="border rounded-3 p-3 bg-light text-start font-monospace small mb-3">
                    <div class="text-center fw-bold border-bottom pb-2 mb-2">
                        <div>{{ $issued->location?->name }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">PARKING ENTRY RECEIPT</div>
                    </div>

                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Ticket No:</span>
                        <strong class="text-primary">{{ $issued->ticket_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Plate:</span>
                        <strong class="text-dark">{{ $issued->plate_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Vehicle:</span>
                        <span>{{ $issued->vehicleType?->name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Entry Time:</span>
                        <span>{{ $issued->entry_time->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Gate:</span>
                        <span>{{ $issued->entryGate?->code }}</span>
                    </div>

                    <!-- Barcode Visual -->
                    <div class="barcode-strip text-center"></div>
                    <div class="text-center font-monospace text-muted" style="font-size: 0.75rem;">*{{ $issued->barcode_hash }}*</div>

                    <div class="text-center text-muted border-top pt-2 mt-2" style="font-size: 0.68rem;">
                        Grace Period: 15 Mins • Do not leave ticket in car • Lost ticket penalty applies.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('tickets.print', $issued->id) }}" target="_blank" class="btn btn-outline-primary flex-fill">
                        <i class="bi bi-printer me-1"></i>Print Ticket
                    </a>
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
    function generateRandomPlate() {
        const letters = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        const prefix = "B";
        const num = Math.floor(1000 + Math.random() * 9000);
        const suffix = letters.charAt(Math.floor(Math.random() * letters.length)) +
                       letters.charAt(Math.floor(Math.random() * letters.length)) +
                       letters.charAt(Math.floor(Math.random() * letters.length));
        const plate = `${prefix} ${num} ${suffix}`;
        document.getElementById('plate_number').value = plate;
        document.getElementById('camera-overlay-plate').textContent = plate;
    }

    function filterSlotsByZone(zoneId) {
        const slotSelect = document.getElementById('slot_select');
        if (!slotSelect) return;
        const options = slotSelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) {
                opt.hidden = false;
                return;
            }
            if (!zoneId || opt.getAttribute('data-zone-id') === zoneId) {
                opt.hidden = false;
            } else {
                opt.hidden = true;
            }
        });

        const selectedOpt = slotSelect.selectedOptions[0];
        if (selectedOpt && selectedOpt.hidden) {
            slotSelect.value = "";
        }
    }

    // Show modal if ticket was issued
    document.addEventListener("DOMContentLoaded", function () {
        const modalEl = document.getElementById('ticketModal');
        if (modalEl) {
            const ticketModal = new bootstrap.Modal(modalEl);
            ticketModal.show();
        }

        const slotSelect = document.getElementById('slot_select');
        if (slotSelect) {
            slotSelect.addEventListener('change', function () {
                const opt = this.selectedOptions[0];
                if (opt && opt.getAttribute('data-zone-id')) {
                    const zoneSelect = document.getElementById('zone_select');
                    if (zoneSelect && !zoneSelect.value) {
                        zoneSelect.value = opt.getAttribute('data-zone-id');
                    }
                }
            });
        }
    });
</script>
@endsection
