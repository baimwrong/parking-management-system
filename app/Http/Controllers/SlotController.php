<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingZone;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $zones = ParkingZone::where('is_active', true)
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->withCount(['slots as total_slots_count',
                'slots as available_slots_count' => fn($q) => $q->where('status', 'available'),
                'slots as occupied_slots_count' => fn($q) => $q->where('status', 'occupied'),
                'slots as maintenance_slots_count' => fn($q) => $q->where('status', 'maintenance'),
            ])
            ->get();

        $selectedZoneId = $request->query('zone_id');
        $statusFilter = $request->query('status');
        $typeFilter = $request->query('vehicle_type');

        $slots = ParkingSlot::with(['zone', 'activeTicket.vehicleType'])
            ->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->whereHas('zone', fn($z) => $z->where('parking_location_id', $selectedLocation->id));
            })
            ->when($selectedZoneId, fn($q) => $q->where('parking_zone_id', $selectedZoneId))
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($typeFilter, fn($q) => $q->where('vehicle_type', $typeFilter))
            ->orderBy('slot_number')
            ->paginate(40);

        return view('slots.index', compact('locations', 'selectedLocation', 'zones', 'slots', 'selectedZoneId', 'statusFilter', 'typeFilter'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parking_zone_id' => 'required|exists:parking_zones,id',
            'slot_number' => 'required|string|max:50',
            'vehicle_type' => 'required|string|in:car,motorcycle,suv,ev,truck,bus',
            'notes' => 'nullable|string|max:255',
        ]);

        $slotNumber = strtoupper(trim($request->slot_number));

        $exists = ParkingSlot::where('parking_zone_id', $request->parking_zone_id)
            ->where('slot_number', $slotNumber)
            ->exists();

        if ($exists) {
            return back()->with('error', "Slot {$slotNumber} already exists in this zone.");
        }

        $slot = ParkingSlot::create([
            'parking_zone_id' => $request->parking_zone_id,
            'slot_number' => $slotNumber,
            'vehicle_type' => $request->vehicle_type,
            'status' => 'available',
            'notes' => $request->notes,
            'is_active' => true,
        ]);

        $slot->zone->recalculateAvailableSlots();

        AuditLog::log('CREATE_SLOT', 'SLOTS', "Created parking slot {$slotNumber}", (string) $slot->id);

        return back()->with('success', "Parking Slot {$slotNumber} created successfully.");
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:available,occupied,reserved,maintenance',
        ]);

        $slot = ParkingSlot::findOrFail($id);

        if ($slot->status === 'occupied' && $request->status === 'available') {
            // Check if active ticket exists
            if ($slot->activeTicket) {
                return back()->with('error', "Cannot set slot to available while vehicle {$slot->activeTicket->plate_number} is still parked.");
            }
        }

        $old = $slot->status;
        $slot->status = $request->status;
        $slot->save();

        if ($slot->zone) {
            $slot->zone->recalculateAvailableSlots();
        }

        AuditLog::log('UPDATE_SLOT_STATUS', 'SLOTS', "Changed slot {$slot->slot_number} status from {$old} to {$slot->status}", (string) $slot->id);

        return back()->with('success', "Slot {$slot->slot_number} status changed to {$slot->status}.");
    }
}
