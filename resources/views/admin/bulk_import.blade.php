@extends('layouts.app')

@section('title', 'Bulk Import Center')
@section('page-title', 'Administration • Bulk Data Import Center')

@section('content')
<div class="row g-4">
    <!-- Left Column: Bulk Import Uploader -->
    <div class="col-lg-7">
        <div class="card-custom p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="p-3 bg-primary-subtle text-primary rounded-circle fs-3">
                    <i class="bi bi-file-earmark-arrow-up-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Bulk Data Ingestion Engine</h5>
                    <p class="text-muted small mb-0">Upload pre-formatted CSV files to bulk populate slots, members, tariffs, or registered vehicles.</p>
                </div>
            </div>

            <!-- Import Result Feedback Banner -->
            @if(session('import_result'))
            @php $res = session('import_result'); @endphp
            <div class="p-4 rounded-3 border mb-4 {{ $res['failed'] > 0 ? 'bg-warning-subtle border-warning' : 'bg-success-subtle border-success' }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 {{ $res['failed'] > 0 ? 'text-warning-emphasis' : 'text-success-emphasis' }}">
                        <i class="bi {{ $res['failed'] > 0 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }} me-2"></i>
                        Import Summary: {{ $res['successful'] }} Succeeded, {{ $res['failed'] }} Failed
                    </h6>
                    <span class="badge bg-white text-dark border">Total Rows: {{ $res['total'] }}</span>
                </div>

                @if(!empty($res['errors']))
                <div class="mt-3 pt-3 border-top border-secondary-subtle">
                    <div class="small fw-bold text-danger mb-2"><i class="bi bi-x-circle me-1"></i>Row-by-Row Error Log:</div>
                    <ul class="small mb-0 text-danger ps-3">
                        @foreach($res['errors'] as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            <form action="{{ route('admin.bulk_import.process') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary mb-2">Select Import Module <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <input type="radio" class="btn-check" name="import_type" id="type_slots" value="slots" checked onchange="updateTemplateHint('slots')">
                            <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center gap-3" for="type_slots">
                                <i class="bi bi-layout-wtf fs-3 text-primary"></i>
                                <div>
                                    <div class="fw-bold small">Parking Bays / Slots</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Zone code, bay numbers, vehicle types</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-sm-6">
                            <input type="radio" class="btn-check" name="import_type" id="type_members" value="members" onchange="updateTemplateHint('members')">
                            <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center gap-3" for="type_members">
                                <i class="bi bi-person-badge fs-3 text-info"></i>
                                <div>
                                    <div class="fw-bold small">Member Passes</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">RFID, member details, plate, validity</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-sm-6">
                            <input type="radio" class="btn-check" name="import_type" id="type_tariffs" value="tariffs" onchange="updateTemplateHint('tariffs')">
                            <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center gap-3" for="type_tariffs">
                                <i class="bi bi-calculator fs-3 text-success"></i>
                                <div>
                                    <div class="fw-bold small">Tariff Pricing Rates</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Progressive hourly, caps, penalties</div>
                                </div>
                            </label>
                        </div>

                        <div class="col-sm-6">
                            <input type="radio" class="btn-check" name="import_type" id="type_vehicles" value="vehicles" onchange="updateTemplateHint('vehicles')">
                            <label class="btn btn-outline-secondary w-100 text-start p-3 d-flex align-items-center gap-3" for="type_vehicles">
                                <i class="bi bi-shield-check fs-3 text-warning"></i>
                                <div>
                                    <div class="fw-bold small">Registered Vehicles</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">VIP whitelist, tenant vehicle catalog</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- CSV File Upload -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Upload CSV File <span class="text-danger">*</span></label>
                    <input type="file" name="csv_file" class="form-control form-control-lg" accept=".csv,text/csv" required>
                    <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Only standard UTF-8 CSV files supported. Maximum file size: 5MB.</small>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 shadow-sm">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>Validate & Ingest CSV Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Downloadable Templates & Documentation -->
    <div class="col-lg-5">
        <!-- Sample Templates Card -->
        <div class="card-custom p-4 mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet-fill me-2 text-success"></i>Official CSV Data Templates</h6>
            <p class="small text-muted mb-3">Download pre-configured CSV templates with valid column headers and sample data rows ready to populate.</p>

            <div class="d-flex flex-column gap-2 mb-3">
                <a href="{{ asset('templates/slots_template.csv') }}" download class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center p-2">
                    <div>
                        <i class="bi bi-download text-primary me-2"></i>
                        <span class="fw-semibold small">slots_template.csv</span>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Zone Code, Slot Number, Vehicle Type, Notes</small>
                    </div>
                    <span class="badge bg-light text-dark border">Download</span>
                </a>

                <a href="{{ asset('templates/members_template.csv') }}" download class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center p-2">
                    <div>
                        <i class="bi bi-download text-primary me-2"></i>
                        <span class="fw-semibold small">members_template.csv</span>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Pass #, RFID, Name, Phone, Plate, Valid Dates</small>
                    </div>
                    <span class="badge bg-light text-dark border">Download</span>
                </a>

                <a href="{{ asset('templates/tariffs_template.csv') }}" download class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center p-2">
                    <div>
                        <i class="bi bi-download text-primary me-2"></i>
                        <span class="fw-semibold small">tariffs_template.csv</span>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Location Code, Vehicle Type, Rates, Caps, Grace</small>
                    </div>
                    <span class="badge bg-light text-dark border">Download</span>
                </a>

                <a href="{{ asset('templates/vehicles_template.csv') }}" download class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center p-2">
                    <div>
                        <i class="bi bi-download text-primary me-2"></i>
                        <span class="fw-semibold small">vehicles_template.csv</span>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">Plate Number, Vehicle Type, Owner Name, Notes</small>
                    </div>
                    <span class="badge bg-light text-dark border">Download</span>
                </a>
            </div>

            <div class="p-3 bg-light rounded-3 border small">
                <div class="fw-bold text-dark mb-1"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Import Tips:</div>
                <ul class="mb-0 text-muted ps-3">
                    <li>Duplicate slot numbers in the same zone will update existing bay properties.</li>
                    <li>Member passes with existing pass numbers will update existing member records.</li>
                    <li>Date format must be <span class="font-monospace text-dark">YYYY-MM-DD</span>.</li>
                </ul>
            </div>
        </div>

        <!-- Historical Import Logs -->
        <div class="card-custom p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Import Ingestions</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th>File / Type</th>
                            <th>Status</th>
                            <th>Success / Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentImports as $imp)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $imp->filename }}</strong>
                                <small class="text-muted d-block text-uppercase">{{ $imp->import_type }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $imp->status === 'completed' ? 'bg-success' : ($imp->status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                    {{ ucfirst($imp->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="font-monospace fw-semibold">{{ $imp->successful_rows }} / {{ $imp->total_rows }}</span>
                            </td>
                            <td>
                                <small class="text-muted">{{ $imp->created_at->format('M d, H:i') }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No bulk imports recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
