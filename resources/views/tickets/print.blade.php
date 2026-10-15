<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $ticket->ticket_number }} | Print Slip</title>

    <!-- Local Bootstrap 5.3 & Icons (Offline Intranet Capable) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: "Courier New", Courier, monospace;
            padding: 2rem 1rem;
        }

        .thermal-slip {
            width: 320px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            color: #000000;
        }

        .barcode-box {
            background: repeating-linear-gradient(90deg, #000, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 7px, #fff 7px, #fff 9px);
            height: 52px;
            margin: 12px 0;
        }

        .line-dashed {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .thermal-slip {
                border: none;
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="text-center mb-3 no-print">
    <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
        <i class="bi bi-printer me-1"></i>Print Slip
    </button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm">Close Window</button>
</div>

<div class="thermal-slip">
    <div class="text-center mb-2">
        <h5 class="fw-bold mb-0 text-uppercase">{{ $ticket->location?->name ?? 'Parking Management System' }}</h5>
        <div style="font-size: 0.75rem;">{{ $ticket->location?->address ?? 'Parking Facility Terminal' }}</div>
        <div style="font-size: 0.75rem;">TEL: {{ $ticket->location?->contact_phone ?? '1-800-PARKING' }}</div>
    </div>

    <div class="line-dashed"></div>

    <div class="text-center fw-bold mb-2">PARKING ENTRY TICKET</div>

    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>TICKET NO:</span>
        <strong>{{ $ticket->ticket_number }}</strong>
    </div>

    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>PLATE:</span>
        <strong style="font-size: 1rem;">{{ $ticket->plate_number }}</strong>
    </div>

    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>VEHICLE:</span>
        <span>{{ strtoupper($ticket->vehicleType?->name) }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>ENTRY GATE:</span>
        <span>{{ $ticket->entryGate?->code ?? 'GATE-01' }}</span>
    </div>

    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>IN TIME:</span>
        <strong>{{ $ticket->entry_time->format('Y-m-d H:i:s') }}</strong>
    </div>

    @if($ticket->slot)
    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
        <span>ASSIGNED BAY:</span>
        <strong>{{ $ticket->slot->slot_number }} ({{ $ticket->zone?->code }})</strong>
    </div>
    @endif

    <div class="line-dashed"></div>

    <!-- Barcode Strip -->
    <div class="text-center">
        <div class="barcode-box"></div>
        <div class="fw-bold" style="letter-spacing: 2px; font-size: 0.85rem;">*{{ $ticket->barcode_hash }}*</div>
    </div>

    <div class="line-dashed"></div>

    <div class="text-center" style="font-size: 0.72rem; line-height: 1.3;">
        <div>15 MINUTES FREE GRACE PERIOD</div>
        <div>RATES: ${{ number_format($ticket->vehicleType?->base_rate, 2) }} 1st HR / ${{ number_format($ticket->vehicleType?->hourly_rate, 2) }} SUBSEQ</div>
        <div class="mt-1">LOST TICKET FEE: ${{ number_format($ticket->vehicleType?->lost_ticket_penalty, 2) }}</div>
        <div class="mt-2 text-muted">Please keep ticket safe & dry. Management assumes no liability for vehicle loss or damage.</div>
    </div>
</div>

<script>
    // Auto-trigger print if requested via query param
    if (window.location.search.includes('autoprint=1')) {
        window.print();
    }
</script>

</body>
</html>
