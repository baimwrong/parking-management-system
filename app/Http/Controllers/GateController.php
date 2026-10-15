<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Gate;
use App\Models\GateLog;
use App\Models\MemberPass;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\ParkingZone;
use App\Models\Payment;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GateController extends Controller
{
    /**
     * Entry Gate Terminal (Check-in Simulator & Camera ANPR)
     */
    public function entryTerminal(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $entryGates = Gate::where('gate_type', 'entry')
            ->where('is_active', true)
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->get();

        $vehicleTypes = VehicleType::where('is_active', true)->get();

        $zones = ParkingZone::where('is_active', true)
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->with(['slots' => fn($q) => $q->where('status', 'available')])
            ->get();

        $recentEntries = ParkingTicket::with(['vehicleType', 'entryGate', 'zone'])
            ->where('status', 'parked')
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->latest('entry_time')
            ->take(10)
            ->get();

        return view('gate.entry', compact('locations', 'selectedLocation', 'entryGates', 'vehicleTypes', 'zones', 'recentEntries'));
    }

    /**
     * Check-in vehicle (Dispense Ticket & Open Barrier)
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
            'entry_gate_id' => 'required|exists:gates,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'plate_number' => 'required|string|max:20',
            'parking_zone_id' => 'nullable|exists:parking_zones,id',
            'parking_slot_id' => 'nullable|exists:parking_slots,id',
        ]);

        $plateNumber = strtoupper(trim(preg_replace('/\s+/', ' ', $request->plate_number)));

        // Check if vehicle is already parked inside
        $activeTicket = ParkingTicket::where('plate_number', $plateNumber)
            ->where('status', 'parked')
            ->first();

        if ($activeTicket) {
            return back()->with('error', "Vehicle {$plateNumber} is already recorded inside the facility with Ticket #{$activeTicket->ticket_number} (Entered: {$activeTicket->entry_time->format('H:i')}).");
        }

        // Check if plate belongs to an active Member Pass
        $memberPass = MemberPass::where('plate_number', $plateNumber)
            ->where('status', 'active')
            ->where('valid_until', '>=', Carbon::today())
            ->first();

        $ticketType = $memberPass ? 'member' : 'casual';

        DB::beginTransaction();
        try {
            // Find and allocate slot if requested or automatically find first available in zone
            $slot = null;
            if ($request->filled('parking_slot_id')) {
                $slot = ParkingSlot::find($request->parking_slot_id);
            } elseif ($request->filled('parking_zone_id')) {
                $slot = ParkingSlot::where('parking_zone_id', $request->parking_zone_id)
                    ->where('status', 'available')
                    ->first();
            }

            if ($slot && $slot->status === 'available') {
                $slot->status = 'occupied';
                $slot->save();

                if ($slot->zone) {
                    $slot->zone->recalculateAvailableSlots();
                }
            }

            $dateCode = Carbon::now()->format('Ymd');
            $dailyCount = ParkingTicket::whereDate('entry_time', Carbon::today())->count() + 1;
            $ticketNumber = sprintf('TKT-%s-%04d', $dateCode, $dailyCount);

            $ticket = ParkingTicket::create([
                'ticket_uid' => (string) Str::uuid(),
                'ticket_number' => $ticketNumber,
                'barcode_hash' => strtoupper(Str::random(12)),
                'parking_location_id' => $request->parking_location_id,
                'parking_zone_id' => $slot ? $slot->parking_zone_id : $request->parking_zone_id,
                'parking_slot_id' => $slot ? $slot->id : null,
                'entry_gate_id' => $request->entry_gate_id,
                'vehicle_type_id' => $request->vehicle_type_id,
                'plate_number' => $plateNumber,
                'ticket_type' => $ticketType,
                'member_pass_id' => $memberPass?->id,
                'entry_time' => Carbon::now(),
                'status' => 'parked',
            ]);

            // Gate log
            GateLog::create([
                'gate_id' => $request->entry_gate_id,
                'ticket_id' => $ticket->id,
                'action' => 'ticket_dispensed',
                'plate_number_detected' => $plateNumber,
                'confidence_score' => 99.40,
                'operator_user_id' => Auth::id(),
                'reason' => $memberPass ? 'Valid Member Pass Recognized' : 'Casual Ticket Dispensed',
                'created_at' => now(),
            ]);

            AuditLog::log('CHECK_IN', 'TICKETS', "Vehicle {$plateNumber} checked in via Ticket {$ticketNumber}", (string) $ticket->id);

            DB::commit();

            return redirect()->route('gate.entry', ['location_id' => $request->parking_location_id])
                ->with('ticket_issued', $ticket->load(['vehicleType', 'entryGate', 'zone', 'slot', 'location']));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Check-in failed: ' . $e->getMessage());
        }
    }

    /**
     * Exit Gate Terminal & Cashier POS
     */
    public function exitTerminal(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $exitGates = Gate::where('gate_type', 'exit')
            ->where('is_active', true)
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->get();

        $activeTicket = null;
        $calculation = null;

        if ($request->filled('search_term')) {
            $term = strtoupper(trim($request->search_term));
            $activeTicket = ParkingTicket::with(['vehicleType', 'entryGate', 'zone', 'slot', 'location', 'memberPass'])
                ->where('status', 'parked')
                ->where(function ($q) use ($term) {
                    $q->where('ticket_number', $term)
                        ->orWhere('barcode_hash', $term)
                        ->orWhere('plate_number', $term);
                })
                ->latest('entry_time')
                ->first();

            if ($activeTicket) {
                $calculation = $activeTicket->recalculateCharges();
            } else {
                session()->flash('warning', "No active parked ticket found matching '{$term}'.");
            }
        }

        $recentExits = ParkingTicket::with(['vehicleType', 'exitGate', 'cashier'])
            ->where('status', 'completed')
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->latest('exit_time')
            ->take(8)
            ->get();

        return view('gate.exit', compact('locations', 'selectedLocation', 'exitGates', 'activeTicket', 'calculation', 'recentExits'));
    }

    /**
     * Process Exit Payment & Clear Barrier
     */
    public function processExitPayment(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:parking_tickets,id',
            'exit_gate_id' => 'required|exists:gates,id',
            'payment_method' => 'required|string|in:cash,qris_ewallet,credit_card,debit_card,member_card,complimentary',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $ticket = ParkingTicket::with(['vehicleType', 'slot', 'zone'])->findOrFail($request->ticket_id);

        if ($ticket->status !== 'parked') {
            return back()->with('error', "Ticket #{$ticket->ticket_number} has already been settled (Status: {$ticket->status}).");
        }

        $exitTime = Carbon::now();
        $calc = $ticket->recalculateCharges($exitTime);
        $totalDue = $calc['total'];
        $amountPaid = (float) $request->amount_paid;

        // If not member/free, verify payment
        if ($totalDue > 0 && $amountPaid < $totalDue && $request->payment_method !== 'complimentary') {
            return back()->with('error', "Amount tendered (\${$amountPaid}) is less than the total due (\${$totalDue}).");
        }

        $changeGiven = max(0, $amountPaid - $totalDue);

        DB::beginTransaction();
        try {
            $invoiceNumber = 'INV-' . Carbon::now()->format('Ymd') . '-' . sprintf('%04d', $ticket->id);
            $referenceNo = 'REF-' . strtoupper(Str::random(10));

            $payment = Payment::create([
                'parking_ticket_id' => $ticket->id,
                'invoice_number' => $invoiceNumber,
                'payment_method' => $request->payment_method,
                'amount_due' => $totalDue,
                'amount_paid' => $amountPaid,
                'change_given' => $changeGiven,
                'transaction_status' => 'success',
                'reference_no' => $referenceNo,
                'cashier_user_id' => Auth::id(),
                'paid_at' => $exitTime,
                'receipt_meta' => [
                    'plate_number' => $ticket->plate_number,
                    'duration_minutes' => $calc['duration_minutes'],
                    'duration_formatted' => floor($calc['duration_minutes'] / 60) . 'h ' . ($calc['duration_minutes'] % 60) . 'm',
                    'subtotal' => $calc['subtotal'],
                    'tax' => $calc['tax'],
                    'penalty' => $calc['penalty'],
                    'cashier' => Auth::user()?->name ?? 'System Automated Kiosk',
                ],
            ]);

            // Free slot if allocated
            if ($ticket->slot) {
                $ticket->slot->status = 'available';
                $ticket->slot->save();

                if ($ticket->slot->zone) {
                    $ticket->slot->zone->recalculateAvailableSlots();
                }
            }

            // Update ticket
            $ticket->exit_gate_id = $request->exit_gate_id;
            $ticket->exit_time = $exitTime;
            $ticket->paid_at = $exitTime;
            $ticket->status = 'completed';
            $ticket->payment_method = $request->payment_method;
            $ticket->payment_reference = $referenceNo;
            $ticket->cashier_user_id = Auth::id();
            $ticket->save();

            // Log exit gate event
            GateLog::create([
                'gate_id' => $request->exit_gate_id,
                'ticket_id' => $ticket->id,
                'action' => 'exit_cleared',
                'plate_number_detected' => $ticket->plate_number,
                'confidence_score' => 99.10,
                'operator_user_id' => Auth::id(),
                'reason' => "Payment \${$totalDue} processed via {$request->payment_method}. Barrier Opened.",
                'created_at' => $exitTime,
            ]);

            AuditLog::log('CHECK_OUT', 'PAYMENTS', "Settled Ticket {$ticket->ticket_number} for \${$totalDue}", (string) $payment->id);

            DB::commit();

            return redirect()->route('gate.exit', ['location_id' => $ticket->parking_location_id])
                ->with('payment_settled', $payment->load('ticket.vehicleType', 'ticket.entryGate', 'ticket.location'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Lost Ticket Recovery Wizard
     */
    public function lostTicket(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $vehicleTypes = VehicleType::where('is_active', true)->get();
        $gates = Gate::where('gate_type', 'exit')->where('is_active', true)->get();

        return view('gate.lost_ticket', compact('locations', 'vehicleTypes', 'gates'));
    }

    /**
     * Resolve Lost Ticket and Issue Exit Clearance
     */
    public function processLostTicket(Request $request)
    {
        $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
            'exit_gate_id' => 'required|exists:gates,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'plate_number' => 'required|string|max:20',
            'estimated_entry_time' => 'required|date|before_or_equal:now',
            'driver_id_number' => 'required|string|max:50',
            'driver_phone' => 'required|string|max:30',
            'payment_method' => 'required|string|in:cash,qris_ewallet,credit_card,debit_card',
            'amount_paid' => 'required|numeric|min:0',
        ]);

        $vType = VehicleType::findOrFail($request->vehicle_type_id);
        $entryTime = Carbon::parse($request->estimated_entry_time);
        $exitTime = Carbon::now();
        $durationMinutes = max(1, $entryTime->diffInMinutes($exitTime));

        $plateNumber = strtoupper(trim($request->plate_number));
        $lostPenalty = (float) $vType->lost_ticket_penalty;
        $tax = round($lostPenalty * 0.10, 2);
        $totalDue = $lostPenalty + $tax;

        $amountPaid = (float) $request->amount_paid;
        if ($amountPaid < $totalDue) {
            return back()->with('error', "Amount tendered (\${$amountPaid}) is less than the required penalty total (\${$totalDue}).");
        }

        DB::beginTransaction();
        try {
            $ticketNumber = sprintf('LOST-%s-%04d', date('Ymd'), rand(100, 999));

            $ticket = ParkingTicket::create([
                'ticket_uid' => (string) Str::uuid(),
                'ticket_number' => $ticketNumber,
                'barcode_hash' => strtoupper(Str::random(12)),
                'parking_location_id' => $request->parking_location_id,
                'exit_gate_id' => $request->exit_gate_id,
                'vehicle_type_id' => $vType->id,
                'plate_number' => $plateNumber,
                'ticket_type' => 'lost',
                'entry_time' => $entryTime,
                'exit_time' => $exitTime,
                'paid_at' => $exitTime,
                'status' => 'completed',
                'duration_minutes' => $durationMinutes,
                'subtotal' => $lostPenalty,
                'penalty_amount' => $lostPenalty,
                'tax_amount' => $tax,
                'total_amount' => $totalDue,
                'payment_method' => $request->payment_method,
                'payment_reference' => 'LOST-REF-' . strtoupper(Str::random(8)),
                'cashier_user_id' => Auth::id(),
                'notes' => "Lost ticket affidavit: Driver ID: {$request->driver_id_number}, Phone: {$request->driver_phone}",
            ]);

            $payment = Payment::create([
                'parking_ticket_id' => $ticket->id,
                'invoice_number' => 'INV-LOST-' . date('Ymd') . '-' . $ticket->id,
                'payment_method' => $request->payment_method,
                'amount_due' => $totalDue,
                'amount_paid' => $amountPaid,
                'change_given' => max(0, $amountPaid - $totalDue),
                'transaction_status' => 'success',
                'reference_no' => $ticket->payment_reference,
                'cashier_user_id' => Auth::id(),
                'paid_at' => $exitTime,
                'receipt_meta' => [
                    'plate_number' => $plateNumber,
                    'reason' => 'Lost Ticket Affidavit Replacement',
                    'driver_id' => $request->driver_id_number,
                    'penalty_charged' => $lostPenalty,
                ],
            ]);

            GateLog::create([
                'gate_id' => $request->exit_gate_id,
                'ticket_id' => $ticket->id,
                'action' => 'exit_cleared',
                'plate_number_detected' => $plateNumber,
                'confidence_score' => 98.00,
                'operator_user_id' => Auth::id(),
                'reason' => "Lost ticket resolved, barrier opened for {$plateNumber}",
                'created_at' => $exitTime,
            ]);

            AuditLog::log('LOST_TICKET', 'TICKETS', "Resolved lost ticket for plate {$plateNumber}. Penalty: \${$totalDue}", (string) $ticket->id);

            DB::commit();

            return redirect()->route('gate.exit', ['location_id' => $request->parking_location_id])
                ->with('payment_settled', $payment->load('ticket.vehicleType', 'ticket.location'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lost ticket processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual Barrier Gate Override
     */
    public function barrierOverride(Request $request)
    {
        $request->validate([
            'gate_id' => 'required|exists:gates,id',
            'action' => 'required|string|in:open,close',
            'reason' => 'required|string|min:5|max:255',
        ]);

        $gate = Gate::findOrFail($request->gate_id);
        $gate->barrier_status = $request->action === 'open' ? 'open' : 'closed';
        $gate->save();

        GateLog::create([
            'gate_id' => $gate->id,
            'action' => 'manual_override',
            'operator_user_id' => Auth::id(),
            'reason' => "Manual command [{$request->action}]: {$request->reason}",
            'created_at' => now(),
        ]);

        AuditLog::log('BARRIER_OVERRIDE', 'GATES', "Gate {$gate->code} set to {$gate->barrier_status}. Reason: {$request->reason}", (string) $gate->id);

        return back()->with('success', "Barrier Gate {$gate->code} successfully set to {$gate->barrier_status}. Action logged.");
    }
}
