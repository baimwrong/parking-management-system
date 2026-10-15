<?php

namespace App\Http\Controllers;

use App\Models\Gate;
use App\Models\ParkingLocation;
use App\Models\ParkingTicket;
use App\Models\Payment;
use App\Models\VehicleType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $locations = ParkingLocation::where('is_active', true)->get();
        $selectedLocation = ParkingLocation::find($request->query('location_id', $locations->first()?->id)) ?? $locations->first();

        $startDate = $request->query('start_date', Carbon::today()->subDays(6)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Total Revenue in Period
        $totalRevenue = Payment::where('transaction_status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->whereHas('ticket', fn($t) => $t->where('parking_location_id', $selectedLocation->id));
            })
            ->sum('amount_paid');

        // 2. Total Sessions
        $totalSessions = ParkingTicket::whereBetween('entry_time', [$start, $end])
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->count();

        // 3. Completed / Settled Sessions
        $completedSessions = ParkingTicket::where('status', 'completed')
            ->whereBetween('exit_time', [$start, $end])
            ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
            ->count();

        // 4. Revenue by Payment Method
        $paymentMethods = Payment::selectRaw('payment_method, count(*) as count, sum(amount_paid) as total')
            ->where('transaction_status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->whereHas('ticket', fn($t) => $t->where('parking_location_id', $selectedLocation->id));
            })
            ->groupBy('payment_method')
            ->get();

        // 5. Revenue by Vehicle Type
        $vehicleTypes = VehicleType::all();
        $revenueByVehicleType = [];
        foreach ($vehicleTypes as $vt) {
            $sum = Payment::where('transaction_status', 'success')
                ->whereBetween('paid_at', [$start, $end])
                ->whereHas('ticket', function ($t) use ($vt, $selectedLocation) {
                    $t->where('vehicle_type_id', $vt->id)
                        ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id));
                })
                ->sum('amount_paid');

            $revenueByVehicleType[] = [
                'type' => $vt,
                'total' => $sum,
            ];
        }

        // 6. Daily Trend in Range
        $dailyTrend = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();

            $rev = Payment::where('transaction_status', 'success')
                ->whereBetween('paid_at', [$dayStart, $dayEnd])
                ->when($selectedLocation, function ($q) use ($selectedLocation) {
                    $q->whereHas('ticket', fn($t) => $t->where('parking_location_id', $selectedLocation->id));
                })
                ->sum('amount_paid');

            $entries = ParkingTicket::whereBetween('entry_time', [$dayStart, $dayEnd])
                ->when($selectedLocation, fn($q) => $q->where('parking_location_id', $selectedLocation->id))
                ->count();

            $dailyTrend[] = [
                'date' => $current->format('M d (D)'),
                'revenue' => $rev,
                'entries' => $entries,
            ];

            $current->addDay();
        }

        return view('reports.index', compact(
            'locations',
            'selectedLocation',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalSessions',
            'completedSessions',
            'paymentMethods',
            'revenueByVehicleType',
            'dailyTrend'
        ));
    }

    public function exportCsv(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::today()->subDays(6)->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $payments = Payment::with(['ticket.vehicleType', 'ticket.location', 'cashier'])
            ->where('transaction_status', 'success')
            ->whereBetween('paid_at', [$start, $end])
            ->latest('paid_at')
            ->get();

        $filename = "parking_financial_report_{$startDate}_to_{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice Number', 'Ticket Number', 'Plate Number', 'Vehicle Type', 'Location', 'Payment Method', 'Amount Paid ($)', 'Paid At', 'Cashier']);

            foreach ($payments as $p) {
                fputcsv($file, [
                    $p->invoice_number,
                    $p->ticket?->ticket_number ?? 'N/A',
                    $p->ticket?->plate_number ?? 'N/A',
                    $p->ticket?->vehicleType?->name ?? 'N/A',
                    $p->ticket?->location?->name ?? 'N/A',
                    strtoupper($p->payment_method),
                    number_format($p->amount_paid, 2),
                    $p->paid_at->toDateTimeString(),
                    $p->cashier?->name ?? 'Automated',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
