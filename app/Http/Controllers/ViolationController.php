<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ViolationController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $status = $request->query('status', 'all');
        $violations = ParkingViolation::with(['location', 'slot.zone', 'officer'])
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $availableSlots = ParkingSlot::where('is_active', true)->get();

        return view('violations.index', compact('locations', 'selectedLocation', 'violations', 'status', 'availableSlots'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
            'plate_number' => 'required|string|max:20',
            'violation_type' => 'required|string|in:overstay,unauthorized_slot,blocking_aisle,clamped,no_ticket',
            'fine_amount' => 'required|numeric|min:0',
            'parking_slot_id' => 'nullable|exists:parking_slots,id',
            'notes' => 'required|string|max:500',
        ]);

        $plateNumber = strtoupper(trim($request->plate_number));
        $status = $request->violation_type === 'clamped' ? 'clamped' : 'issued';

        $violation = ParkingViolation::create([
            'parking_location_id' => $request->parking_location_id,
            'parking_slot_id' => $request->parking_slot_id,
            'plate_number' => $plateNumber,
            'violation_type' => $request->violation_type,
            'fine_amount' => $request->fine_amount,
            'status' => $status,
            'officer_user_id' => Auth::id(),
            'notes' => $request->notes,
        ]);

        AuditLog::log('ISSUE_VIOLATION', 'VIOLATIONS', "Issued violation ticket for vehicle {$plateNumber} (\${$request->fine_amount})", (string) $violation->id);

        return back()->with('success', "Violation notice issued for vehicle {$plateNumber}. Fine: \${$request->fine_amount}.");
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:paid,waived,clamped,issued',
            'reason' => 'nullable|string|max:255',
        ]);

        $violation = ParkingViolation::findOrFail($id);
        $old = $violation->status;
        $violation->status = $request->status;
        if ($request->filled('reason')) {
            $violation->notes = ($violation->notes ? $violation->notes . " | " : "") . "Status update ({$request->status}): " . $request->reason;
        }
        $violation->save();

        AuditLog::log('UPDATE_VIOLATION', 'VIOLATIONS', "Violation #{$violation->id} for {$violation->plate_number} status updated from {$old} to {$request->status}", (string) $violation->id);

        return back()->with('success', "Violation status updated to {$request->status}.");
    }
}
