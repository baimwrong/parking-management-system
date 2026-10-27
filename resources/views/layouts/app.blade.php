<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Intelligent Parking Management System</title>

    <!-- Local Bootstrap 5.3 & Bootstrap Icons (Offline Intranet Capable) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

    <style>
        :root {
            --pf-primary: #0d6efd;
            --pf-primary-dark: #0a4ebd;
            --pf-sidebar-bg: #141c2e;
            --pf-sidebar-hover: #1e293b;
            --pf-sidebar-active: #2563eb;
            --pf-card-bg: #ffffff;
            --pf-body-bg: #f4f6fa;
            --pf-accent-green: #10b981;
            --pf-accent-red: #ef4444;
            --pf-accent-yellow: #f59e0b;
            --pf-accent-purple: #8b5cf6;
        }

        body {
            background-color: var(--pf-body-bg);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        #sidebar-wrapper {
            width: 260px;
            min-height: 100vh;
            background-color: var(--pf-sidebar-bg);
            transition: all 0.3s ease;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .brand-section {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0) 100%);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .brand-badge {
            background: #2563eb;
            color: #fff;
            font-size: 0.65rem;
            padding: 0.2rem 0.45rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            padding: 1.1rem 1.5rem 0.4rem;
            font-weight: 700;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0.5rem 0.75rem;
            margin: 0;
        }

        .sidebar-nav .nav-item {
            margin-bottom: 0.2rem;
        }

        .sidebar-nav .nav-link {
            color: #94a3b8;
            font-size: 0.88rem;
            font-weight: 500;
            padding: 0.65rem 0.9rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.15s ease-in-out;
            text-decoration: none;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
        }

        .sidebar-nav .nav-link:hover {
            color: #f8fafc;
            background-color: var(--pf-sidebar-hover);
        }

        .sidebar-nav .nav-link.active {
            color: #ffffff;
            background-color: var(--pf-sidebar-active);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        /* Main Content Layout */
        #page-content-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Navbar */
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.75rem;
            position: sticky;
            top: 0;
            z-index: 990;
        }

        .main-container {
            padding: 1.75rem;
            flex: 1;
        }

        /* Custom Cards & Elevation */
        .card-custom {
            background-color: var(--pf-card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .card-header-custom {
            background-color: transparent;
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* KPI Metric Cards */
        .kpi-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.1rem;
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .kpi-icon.primary { background-color: #eff6ff; color: #2563eb; }
        .kpi-icon.success { background-color: #ecfdf5; color: #10b981; }
        .kpi-icon.warning { background-color: #fffbeb; color: #f59e0b; }
        .kpi-icon.danger { background-color: #fef2f2; color: #ef4444; }
        .kpi-icon.purple { background-color: #f5f3ff; color: #8b5cf6; }

        .kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }

        .kpi-label {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Parking Slot Visual Grid */
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));
            gap: 12px;
        }

        .slot-box {
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            text-align: center;
            border: 2px solid transparent;
            font-weight: 600;
            transition: all 0.2s ease;
            position: relative;
            cursor: pointer;
        }

        .slot-box.available {
            background-color: #ecfdf5;
            border-color: #10b981;
            color: #065f46;
        }

        .slot-box.occupied {
            background-color: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }

        .slot-box.reserved {
            background-color: #fffbeb;
            border-color: #f59e0b;
            color: #92400e;
        }

        .slot-box.maintenance {
            background-color: #f1f5f9;
            border-color: #94a3b8;
            color: #475569;
        }

        /* Barcode Simulation */
        .barcode-strip {
            font-family: monospace;
            letter-spacing: 5px;
            font-weight: 900;
            font-size: 1.3rem;
            background: repeating-linear-gradient(90deg, #000, #000 2px, #fff 2px, #fff 4px, #000 4px, #000 7px, #fff 7px, #fff 9px);
            color: transparent;
            height: 48px;
            border-radius: 4px;
            margin: 0.75rem 0;
        }

        /* Print media rules */
        @media print {
            #sidebar-wrapper, .top-navbar, .no-print, .btn, .breadcrumb, .modal-backdrop, .modal-header, .modal-footer {
                display: none !important;
            }
            #page-content-wrapper {
                margin-left: 0 !important;
            }
            .main-container {
                padding: 0 !important;
            }
            .modal {
                position: static !important;
                display: block !important;
                background: transparent !important;
            }
            .modal-dialog {
                margin: 0 auto !important;
                max-width: 380px !important;
            }
            .modal-content {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            #printable-receipt-slip {
                border: 1px solid #000 !important;
                background: #fff !important;
                color: #000 !important;
            }
        }
    </style>
    @yield('styles')
</head>
<body>

    <!-- Sidebar Navigation -->
    <div id="sidebar-wrapper">
        <div class="brand-section">
            <a href="{{ route('dashboard') }}" class="brand-logo">
                <i class="bi bi-p-square-fill text-primary" style="font-size: 1.6rem;"></i>
                <div>
                    <div>Parking<span class="text-primary">System</span></div>
                    <span class="brand-badge">Enterprise PMS</span>
                </div>
            </a>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-heading">Operations</li>
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('gate.entry') }}" class="nav-link {{ request()->routeIs('gate.entry') ? 'active' : '' }}">
                    <i class="bi bi-box-arrow-in-right text-success"></i>
                    <span>Entry Terminal (ANPR)</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('gate.exit') }}" class="nav-link {{ request()->routeIs('gate.exit') ? 'active' : '' }}">
                    <i class="bi bi-cash-stack text-warning"></i>
                    <span>Exit Cashier POS</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('gate.lost_ticket') }}" class="nav-link {{ request()->routeIs('gate.lost_ticket') ? 'active' : '' }}">
                    <i class="bi bi-exclamation-octagon text-danger"></i>
                    <span>Lost Ticket Wizard</span>
                </a>
            </li>

            <li class="sidebar-heading">Management</li>
            <li class="nav-item">
                <a href="{{ route('slots.index') }}" class="nav-link {{ request()->routeIs('slots.*') ? 'active' : '' }}">
                    <i class="bi bi-layout-wtf"></i>
                    <span>Zones & Slots</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    <i class="bi bi-ticket-perforated"></i>
                    <span>Parking Tickets</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('members.index') }}" class="nav-link {{ request()->routeIs('members.*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i>
                    <span>Member Passes</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('violations.index') }}" class="nav-link {{ request()->routeIs('violations.*') ? 'active' : '' }}">
                    <i class="bi bi-shield-exclamation text-danger"></i>
                    <span>Enforcement & Fines</span>
                </a>
            </li>

            @if(Auth::check() && Auth::user()->isManager())
            <li class="sidebar-heading">Configuration</li>
            <li class="nav-item">
                <a href="{{ route('tariffs.index') }}" class="nav-link {{ request()->routeIs('tariffs.*') ? 'active' : '' }}">
                    <i class="bi bi-calculator"></i>
                    <span>Tariffs & Rates</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
            @endif

            @if(Auth::check() && Auth::user()->isAdmin())
            <li class="sidebar-heading">Administration</li>
            <li class="nav-item">
                <a href="{{ route('admin.bulk_import.index') }}" class="nav-link {{ request()->routeIs('admin.bulk_import.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-arrow-up-fill text-info"></i>
                    <span>Bulk Import Center</span>
                </a>
            </li>
            @endif
        </ul>

        <div class="px-3 py-4 mt-auto">
            <div class="p-3 rounded-3" style="background-color: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>System Online</span>
                </div>
                <div class="text-white-50 small">Target DB: <strong class="text-white">PostgreSQL 18</strong></div>
                <div class="text-white-50 small">Network: <strong class="text-white">Local / Intranet Ready</strong></div>
            </div>
        </div>
    </div>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="top-navbar d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 fw-bold">@yield('page-title', 'Dashboard')</h5>
                <span class="badge bg-light text-secondary border px-2 py-1">
                    <i class="bi bi-clock me-1"></i><span id="live-clock">--:--:--</span>
                </span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Facility Status Pill -->
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border">
                    <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                    <small class="fw-semibold text-secondary">Gates Active: <strong class="text-dark">4 Online</strong></small>
                </div>

                <!-- User Dropdown -->
                @if(Auth::check())
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border d-flex align-items-center gap-2 dropdown-toggle px-3 py-1" type="button" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.8rem;">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="text-start d-none d-sm-block">
                            <div class="fw-semibold small leading-tight">{{ Auth::user()->name }}</div>
                            <small class="text-uppercase text-muted" style="font-size: 0.65rem;">{{ Auth::user()->role }}</small>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><h6 class="dropdown-header">Signed in as {{ Auth::user()->email }}</h6></li>
                        <li><span class="dropdown-item-text small text-muted">Badge: {{ Auth::user()->badge_id ?? 'N/A' }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2">
                                    <i class="bi bi-box-arrow-right"></i> Sign Out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                @endif
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-container">
            <!-- Flash Message Alerts -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('warning') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-x-circle-fill fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-diamond me-1"></i>Please correct the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Local Bootstrap 5.3 Bundle (Offline Intranet Capable) -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <script>
        // Real-time live header clock
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            const dateString = now.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                clockEl.textContent = `${dateString} • ${timeString}`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
    @yield('scripts')
</body>
</html>
