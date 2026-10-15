@extends('layouts.app')

@section('title', 'Reports & Analytics')
@section('page-title', 'Financial & Operational Reports')

@section('content')
<div class="card-custom p-4 mb-4">
    <!-- Filter Date Range -->
    <form method="GET" action="{{ route('reports.index') }}" class="row g-3 align-items-end mb-4">
        <input type="hidden" name="location_id" value="{{ $selectedLocation->id }}">

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>

        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>

        <div class="col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-filter me-1"></i>Apply Filter
            </button>
            <a href="{{ route('reports.export', ['start_date' => $startDate, 'end_date' => $endDate, 'location_id' => $selectedLocation->id]) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Financial CSV
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print Report
            </button>
        </div>
    </form>

    <!-- Financial KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card shadow-sm border-start border-4 border-success">
                <div class="kpi-icon success">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <div class="kpi-value text-success">${{ number_format($totalRevenue, 2) }}</div>
                    <div class="kpi-label">Gross Revenue</div>
                    <small class="text-muted">{{ $startDate }} - {{ $endDate }}</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card shadow-sm border-start border-4 border-primary">
                <div class="kpi-icon primary">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="kpi-value">{{ $totalSessions }}</div>
                    <div class="kpi-label">Total Inbound Sessions</div>
                    <small class="text-muted">Recorded Entries</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card shadow-sm border-start border-4 border-warning">
                <div class="kpi-icon warning">
                    <i class="bi bi-receipt"></i>
                </div>
                <div>
                    <div class="kpi-value">{{ $completedSessions }}</div>
                    <div class="kpi-label">Settled Transactions</div>
                    <small class="text-muted">Paid & Exit Cleared</small>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="kpi-card shadow-sm border-start border-4 border-purple">
                <div class="kpi-icon purple">
                    <i class="bi bi-calculator"></i>
                </div>
                <div>
                    @php $avg = $completedSessions > 0 ? ($totalRevenue / $completedSessions) : 0; @endphp
                    <div class="kpi-value">${{ number_format($avg, 2) }}</div>
                    <div class="kpi-label">Average Ticket Yield</div>
                    <small class="text-muted">Per Settled Vehicle</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Breakdown by Payment Method & Vehicle Type -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card-custom h-100 p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-credit-card-2-front-fill me-2 text-primary"></i>Revenue by Payment Method</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Method</th>
                                <th>Transactions</th>
                                <th>Share</th>
                                <th class="text-end">Revenue ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentMethods as $pm)
                            @php
                                $share = $totalRevenue > 0 ? round(($pm->total / $totalRevenue) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong class="text-uppercase">{{ str_replace('_', ' ', $pm->payment_method) }}</strong>
                                </td>
                                <td>{{ $pm->count }} txns</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-fill" style="height: 6px;">
                                            <div class="progress-bar bg-primary" style="width: {{ $share }}%"></div>
                                        </div>
                                        <small class="text-muted">{{ $share }}%</small>
                                    </div>
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    ${{ number_format($pm->total, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No payments recorded in this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-custom h-100 p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-car-front-fill me-2 text-info"></i>Revenue by Vehicle Category</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Category</th>
                                <th>Base Tariff</th>
                                <th class="text-end">Total Collected ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($revenueByVehicleType as $rvt)
                            <tr>
                                <td>
                                    <i class="bi {{ $rvt['type']->icon }} me-2 text-primary"></i>
                                    <strong>{{ $rvt['type']->name }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        ${{ number_format($rvt['type']->base_rate, 2) }} / ${{ number_format($rvt['type']->hourly_rate, 2) }}h
                                    </span>
                                </td>
                                <td class="text-end font-monospace fw-bold text-success">
                                    ${{ number_format($rvt['total'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Revenue & Traffic Table -->
    <div class="card-custom p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar-week-fill me-2 text-primary"></i>Daily Revenue & Traffic Trend</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Entries</th>
                        <th class="text-end">Revenue ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyTrend as $dt)
                    <tr>
                        <td class="fw-semibold">{{ $dt['date'] }}</td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $dt['entries'] }} Vehicles</span>
                        </td>
                        <td class="text-end font-monospace fw-bold text-success">
                            ${{ number_format($dt['revenue'], 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
