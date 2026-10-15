<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\ParkingTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $status = $request->query('status', 'parked');
        $search = $request->query('search');

        $tickets = ParkingTicket::with(['vehicleType', 'entryGate', 'exitGate', 'zone', 'slot', 'location', 'memberPass'])
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($search, function ($q) use ($search) {
                $term = strtoupper(trim($search));
                $q->where('ticket_number', 'LIKE', "%{$term}%")
                    ->orWhere('plate_number', 'LIKE', "%{$term}%")
                    ->orWhere('barcode_hash', 'LIKE', "%{$term}%");
            })
            ->latest('entry_time')
            ->paginate(20)
            ->withQueryString();

        // Update real-time fees for active parked tickets on page display
        foreach ($tickets as $ticket) {
            if ($ticket->status === 'parked') {
                $ticket->recalculateCharges();
            }
        }

        return view('tickets.index', compact('locations', 'selectedLocation', 'tickets', 'status', 'search'));
    }

    public function show($id)
    {
        $ticket = ParkingTicket::with(['vehicleType', 'entryGate', 'exitGate', 'zone', 'slot', 'location', 'memberPass', 'payments.cashier'])
            ->findOrFail($id);

        if ($ticket->status === 'parked') {
            $ticket->recalculateCharges();
        }

        return view('tickets.show', compact('ticket'));
    }

    public function printTicket($id)
    {
        $ticket = ParkingTicket::with(['vehicleType', 'entryGate', 'location', 'zone', 'slot'])
            ->findOrFail($id);

        return view('tickets.print', compact('ticket'));
    }

    public function applyDiscount(Request $request, $id)
    {
        $request->validate([
            'discount_amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $ticket = ParkingTicket::findOrFail($id);
        $oldTotal = $ticket->total_amount;

        $ticket->discount_amount = (float) $request->discount_amount;
        $ticket->total_amount = max(0, ($ticket->subtotal + $ticket->tax_amount + $ticket->penalty_amount) - $ticket->discount_amount);
        $ticket->notes = ($ticket->notes ? $ticket->notes . " | " : "") . "Discount \${$request->discount_amount}: {$request->reason}";
        $ticket->save();

        AuditLog::log('DISCOUNT_APPLIED', 'TICKETS', "Applied discount \${$request->discount_amount} on Ticket {$ticket->ticket_number}. Reason: {$request->reason}", (string) $ticket->id);

        return back()->with('success', "Discount of \${$request->discount_amount} applied successfully.");
    }
}
