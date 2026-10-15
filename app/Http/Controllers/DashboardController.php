<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\GateLog;
use App\Models\MemberPass;
use App\Models\ParkingLocation;
use App\Models\ParkingSlot;
use App\Models\ParkingTicket;
use App\Models\ParkingViolation;
use App\Models\Payment;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocationId = $request->query('location_id', $locations->first()?->id);
        $location = ParkingLocation::with(['zones.slots'])->find($selectedLocationId) ?? $locations->first();

        // 1. Occupancy & Vehicle Statistics
        $totalCapacity = $location ? $location->total_capacity : 0;
        $activeTicketsQuery = ParkingTicket::where('status', 'parked')
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id));
        $parkedCount = $activeTicketsQuery->count();
        $availableCapacity = max(0, $totalCapacity - $parkedCount);
        $occupancyRate = $totalCapacity > 0 ? round(($parkedCount / $totalCapacity) * 100, 1) : 0;

        // Vehicle Type breakdown
        $vehicleTypes = VehicleType::where('is_active', true)->get();
        $vehicleBreakdown = [];
        foreach ($vehicleTypes as $vt) {
            $count = ParkingTicket::where('status', 'parked')
                ->where('vehicle_type_id', $vt->id)
                ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
                ->count();
            $vehicleBreakdown[] = [
                'type' => $vt,
                'count' => $count,
            ];
        }

        // 2. Financial Metrics (Today)
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $todayRevenue = Payment::where('transaction_status', 'success')
            ->whereBetween('paid_at', [$todayStart, $todayEnd])
            ->when($location, function ($q) use ($location) {
                $q->whereHas('ticket', fn($t) => $t->where('parking_location_id', $location->id));
            })
            ->sum('amount_paid');

        $todayEntries = ParkingTicket::whereBetween('entry_time', [$todayStart, $todayEnd])
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
            ->count();

        $todayExits = ParkingTicket::where('status', 'completed')
            ->whereBetween('exit_time', [$todayStart, $todayEnd])
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
            ->count();

        $activeViolations = ParkingViolation::whereIn('status', ['issued', 'clamped'])
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
            ->count();

        $activeMembers = MemberPass::where('status', 'active')
            ->where('valid_until', '>=', Carbon::today())
            ->count();

        // 3. Hourly Flow Distribution (Today)
        $hourlyFlow = [];
        for ($h = 6; $h <= 22; $h++) {
            $hStart = Carbon::today()->setHour($h)->startOfHour();
            $hEnd = Carbon::today()->setHour($h)->endOfHour();

            $entries = ParkingTicket::whereBetween('entry_time', [$hStart, $hEnd])
                ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
                ->count();

            $exits = ParkingTicket::whereBetween('exit_time', [$hStart, $hEnd])
                ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
                ->count();

            $hourlyFlow[] = [
                'hour' => sprintf('%02d:00', $h),
                'entries' => $entries,
                'exits' => $exits,
            ];
        }

        // 4. Live Gates Status
        $gates = Gate::where('is_active', true)
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
            ->get();

        // 5. Recent Activity Logs
        $recentTickets = ParkingTicket::with(['vehicleType', 'entryGate', 'zone'])
            ->when($location, fn($q) => $q->where('parking_location_id', $location->id))
            ->latest('entry_time')
            ->take(8)
            ->get();

        $recentGateLogs = GateLog::with(['gate', 'operator'])
            ->latest('created_at')
            ->take(6)
            ->get();

        return view('dashboard.index', compact(
            'locations',
            'location',
            'totalCapacity',
            'parkedCount',
            'availableCapacity',
            'occupancyRate',
            'vehicleBreakdown',
            'todayRevenue',
            'todayEntries',
            'todayExits',
            'activeViolations',
            'activeMembers',
            'hourlyFlow',
            'gates',
            'recentTickets',
            'recentGateLogs'
        ));
    }
}
