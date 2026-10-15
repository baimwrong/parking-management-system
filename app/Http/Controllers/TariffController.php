<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ParkingLocation;
use App\Models\Tariff;
use App\Models\VehicleType;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $tariffs = Tariff::with(['location', 'vehicleType'])
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->get();

        $vehicleTypes = VehicleType::where('is_active', true)->get();

        return view('tariffs.index', compact('locations', 'selectedLocation', 'tariffs', 'vehicleTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'parking_location_id' => 'required|exists:parking_locations,id',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'name' => 'required|string|max:100',
            'tariff_type' => 'required|string|in:progressive,flat,hourly,valet',
            'initial_duration_minutes' => 'required|integer|min:1',
            'initial_rate' => 'required|numeric|min:0',
            'subsequent_rate_per_hour' => 'required|numeric|min:0',
            'max_daily_cap' => 'required|numeric|min:0',
            'overnight_surcharge' => 'nullable|numeric|min:0',
            'lost_ticket_fee' => 'required|numeric|min:0',
            'grace_period_minutes' => 'required|integer|min:0',
        ]);

        $tariff = Tariff::create([
            'parking_location_id' => $request->parking_location_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'name' => $request->name,
            'tariff_type' => $request->tariff_type,
            'initial_duration_minutes' => $request->initial_duration_minutes,
            'initial_rate' => $request->initial_rate,
            'subsequent_rate_per_hour' => $request->subsequent_rate_per_hour,
            'max_daily_cap' => $request->max_daily_cap,
            'overnight_surcharge' => $request->overnight_surcharge ?? 0.00,
            'lost_ticket_fee' => $request->lost_ticket_fee,
            'grace_period_minutes' => $request->grace_period_minutes,
            'is_active' => true,
        ]);

        AuditLog::log('CREATE_TARIFF', 'TARIFFS', "Created tariff '{$tariff->name}'", (string) $tariff->id);

        return back()->with('success', "Tariff '{$tariff->name}' configured successfully.");
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'initial_duration_minutes' => 'required|integer|min:1',
            'initial_rate' => 'required|numeric|min:0',
            'subsequent_rate_per_hour' => 'required|numeric|min:0',
            'max_daily_cap' => 'required|numeric|min:0',
            'overnight_surcharge' => 'nullable|numeric|min:0',
            'lost_ticket_fee' => 'required|numeric|min:0',
            'grace_period_minutes' => 'required|integer|min:0',
        ]);

        $tariff = Tariff::findOrFail($id);
        $tariff->update([
            'name' => $request->name,
            'initial_duration_minutes' => $request->initial_duration_minutes,
            'initial_rate' => $request->initial_rate,
            'subsequent_rate_per_hour' => $request->subsequent_rate_per_hour,
            'max_daily_cap' => $request->max_daily_cap,
            'overnight_surcharge' => $request->overnight_surcharge ?? 0.00,
            'lost_ticket_fee' => $request->lost_ticket_fee,
            'grace_period_minutes' => $request->grace_period_minutes,
        ]);

        AuditLog::log('UPDATE_TARIFF', 'TARIFFS', "Updated tariff rates for '{$tariff->name}'", (string) $tariff->id);

        return back()->with('success', "Tariff '{$tariff->name}' rates updated successfully.");
    }
}
