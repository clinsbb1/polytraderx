<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — PolyTraderX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; }
        .sidebar {
            width: 250px;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .75rem 1.25rem;
            font-size: .9rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link i { margin-right: .5rem; width: 20px; text-align: center; }
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }
        .top-navbar { border-bottom: 1px solid #dee2e6; }
        .dry-run-banner {
            background: #fff3cd;
            color: #856404;
            padding: .5rem 1rem;
            text-align: center;
            font-weight: 500;
            border-bottom: 1px solid #ffc107;
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $settings = app(\App\Services\Settings\SettingsService::class);
        $botEnabled = $settings->getBool('BOT_ENABLED', false);
        $dryRun = $settings->getBool('DRY_RUN', true);
    @endphp

    {{-- Sidebar --}}
    <div class="sidebar bg-dark d-flex flex-column">
        <div class="p-3 border-bottom border-secondary">
            <a href="{{ route('dashboard') }}" class="text-white text-decoration-none fs-5 fw-bold">
                <i class="bi bi-graph-up-arrow"></i> PolyTraderX
            </a>
        </div>

        <nav class="nav flex-column mt-2">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('trades.*') ? 'active' : '' }}" href="{{ route('trades.index') }}">
                <i class="bi bi-currency-exchange"></i> Trades
            </a>
            <a class="nav-link {{ request()->routeIs('audits.*') ? 'active' : '' }}" href="{{ route('audits.index') }}">
                <i class="bi bi-robot"></i> AI Audits
            </a>
            <a class="nav-link {{ request()->routeIs('strategy.*') ? 'active' : '' }}" href="{{ route('strategy.index') }}">
                <i class="bi bi-sliders"></i> Strategy
            </a>
            <a class="nav-link {{ request()->routeIs('balance.*') ? 'active' : '' }}" href="{{ route('balance.index') }}">
                <i class="bi bi-wallet2"></i> Balance
            </a>
            <a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}">
                <i class="bi bi-journal-text"></i> Logs
            </a>
            <a class="nav-link {{ request()->routeIs('ai-costs.*') ? 'active' : '' }}" href="{{ route('ai-costs.index') }}">
                <i class="bi bi-cash-coin"></i> AI Costs
            </a>
        </nav>

        <div class="mt-auto p-3 border-top border-secondary">
            <div class="d-flex align-items-center gap-2">
                @if($botEnabled)
                    <span class="badge bg-success"><i class="bi bi-circle-fill"></i> Bot ON</span>
                @else
                    <span class="badge bg-danger"><i class="bi bi-circle-fill"></i> Bot OFF</span>
                @endif
                @if($dryRun)
                    <span class="badge bg-warning text-dark">DRY RUN</span>
                @else
                    <span class="badge bg-info">LIVE</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="main-content d-flex flex-column">
        {{-- Top Navbar --}}
        <nav class="navbar navbar-light bg-white top-navbar px-4">
            <span class="navbar-text fw-semibold">@yield('title', 'Dashboard')</span>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item" type="submit">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        {{-- Dry Run Banner --}}
        @if($dryRun)
            <div class="dry-run-banner">
                &#9888;&#65039; DRY RUN MODE — No real trades will be placed
            </div>
        @endif

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Page Content --}}
        <div class="p-4 flex-grow-1">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
