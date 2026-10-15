<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Intelligent Parking Management System</title>

    <!-- Local Bootstrap 5.3 & Icons (Offline Intranet Capable) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-header {
            background: #141c2e;
            padding: 2.2rem 2rem 1.8rem;
            text-align: center;
            color: #ffffff;
            border-bottom: 3px solid #2563eb;
        }

        .login-body {
            padding: 2rem;
        }

        .demo-btn {
            font-size: 0.78rem;
            padding: 0.35rem 0.6rem;
            border-radius: 6px;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mb-3" style="width: 58px; height: 58px;">
            <i class="bi bi-p-square-fill text-white fs-2"></i>
        </div>
        <h4 class="fw-bold mb-1">Parking Management System</h4>
        <p class="text-white-50 small mb-0">Enterprise Access & Facility Control</p>
    </div>

    <div class="login-body">
        @if(session('info'))
        <div class="alert alert-info py-2 small border-0">{{ session('info') }}</div>
        @endif

        @if(session('warning'))
        <div class="alert alert-warning py-2 small border-0">{{ session('warning') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger py-2 small border-0">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Email Address or Badge ID</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person-fill text-muted"></i></span>
                    <input type="text" name="email" id="email" class="form-control" placeholder="admin@parking.local or BADGE-001" value="{{ old('email', 'admin@parking.local') }}" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label small fw-semibold text-secondary mb-0">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                    <input type="password" name="password" id="password" class="form-control" value="password" required>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember" checked>
                <label class="form-check-label small text-secondary" for="remember">Keep me logged in on this terminal</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Terminal
            </button>
        </form>

        <!-- Quick Demo Accounts -->
        <div class="mt-4 pt-3 border-top">
            <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                Quick Demo Role Login
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary w-100 demo-btn d-flex align-items-center gap-2" onclick="setCreds('admin@parking.local', 'password')">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <div>
                            <div class="fw-bold">Super Admin</div>
                            <small class="text-muted">Full Control</small>
                        </div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary w-100 demo-btn d-flex align-items-center gap-2" onclick="setCreds('manager@parking.local', 'password')">
                        <i class="bi bi-person-gear text-info"></i>
                        <div>
                            <div class="fw-bold">Manager</div>
                            <small class="text-muted">Tariffs & Slots</small>
                        </div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary w-100 demo-btn d-flex align-items-center gap-2" onclick="setCreds('cashier@parking.local', 'password')">
                        <i class="bi bi-cash-stack text-success"></i>
                        <div>
                            <div class="fw-bold">Gate Cashier</div>
                            <small class="text-muted">POS & Tickets</small>
                        </div>
                    </button>
                </div>
                <div class="col-6">
                    <button type="button" class="btn btn-outline-secondary w-100 demo-btn d-flex align-items-center gap-2" onclick="setCreds('patrol@parking.local', 'password')">
                        <i class="bi bi-shield-fill-exclamation text-danger"></i>
                        <div>
                            <div class="fw-bold">Patrol Officer</div>
                            <small class="text-muted">Enforcement</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 text-muted small">
            <i class="bi bi-hdd-network text-success me-1"></i> Intranet & Offline Ready
        </div>
    </div>
</div>

<script>
    function setCreds(email, pass) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pass;
    }
</script>

</body>
</html>
