<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — PolyTraderX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin.css')) !!}</style>
    @stack('styles')
</head>
<body class="ptx-admin">
    @php
        $settings = app(\App\Services\Settings\SettingsService::class);
        $botEnabled = $settings->getBool('BOT_ENABLED', false);
        $dryRun = $settings->getBool('DRY_RUN', true);
    @endphp

    {{-- Mobile Sidebar Overlay --}}
    <div class="ptx-sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <aside class="ptx-sidebar" id="sidebar">
        <div class="ptx-sidebar-brand">
            <a href="{{ route('dashboard') }}">
                <i class="bi bi-graph-up-arrow" style="color: var(--accent);"></i>
                PolyTrader<span class="brand-x">X</span>
            </a>
        </div>

        <nav class="ptx-sidebar-nav">
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

            <div class="ptx-sidebar-divider"></div>
            <div class="nav-section-label">Settings</div>

            <a class="nav-link {{ request()->is('settings/profile') ? 'active' : '' }}" href="/settings/profile">
                <i class="bi bi-person-gear"></i> Profile
            </a>
            <a class="nav-link {{ request()->is('settings/credentials') ? 'active' : '' }}" href="/settings/credentials">
                <i class="bi bi-key"></i> Polymarket Keys
            </a>
            <a class="nav-link {{ request()->is('settings/telegram') ? 'active' : '' }}" href="/settings/telegram">
                <i class="bi bi-telegram"></i> Telegram
            </a>
            <a class="nav-link {{ request()->is('settings/notifications') ? 'active' : '' }}" href="/settings/notifications">
                <i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link {{ request()->is('subscription*') ? 'active' : '' }}" href="/subscription">
                <i class="bi bi-credit-card"></i> Subscription
            </a>

            @if(auth()->user()->isSuperAdmin())
                <div class="ptx-sidebar-divider"></div>
                <a class="nav-link text-warning" href="/admin">
                    <i class="bi bi-shield-lock"></i> Admin Panel
                </a>
            @endif
        </nav>

        <div class="ptx-sidebar-footer">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($botEnabled)
                    <span class="ptx-badge-bot ptx-badge-on"><i class="bi bi-circle-fill" style="font-size:0.5rem"></i> Bot ON</span>
                @else
                    <span class="ptx-badge-bot ptx-badge-off"><i class="bi bi-circle-fill" style="font-size:0.5rem"></i> Bot OFF</span>
                @endif
                @if($dryRun)
                    <span class="ptx-badge-bot ptx-badge-dry">DRY RUN</span>
                @else
                    <span class="ptx-badge-bot ptx-badge-live">LIVE</span>
                @endif
            </div>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="ptx-main">
        {{-- Top Navbar --}}
        <nav class="ptx-topbar">
            <div class="d-flex align-items-center">
                <button class="ptx-sidebar-toggle" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <span class="ptx-topbar-title">@yield('title', 'Dashboard')</span>
            </div>
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/settings/profile"><i class="bi bi-person-gear"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="/settings/credentials"><i class="bi bi-key"></i> API Keys</a></li>
                    <li><a class="dropdown-item" href="/subscription"><i class="bi bi-credit-card"></i> Subscription</a></li>
                    <li><hr class="dropdown-divider"></li>
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
            <div class="ptx-dryrun-banner">
                <i class="bi bi-exclamation-triangle me-1"></i> DRY RUN MODE — No real trades will be placed
            </div>
        @endif

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="ptx-alert ptx-alert-success mx-4 mt-4 mb-0" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Page Content --}}
        <div class="ptx-content">
            @yield('content')
        </div>
    </div>

    {{-- Toast --}}
    @if(session('toast'))
    <div class="ptx-toast" id="ptxToast">
        <i class="bi bi-check-circle-fill"></i>
        <span>{{ session('toast') }}</span>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }

        // Toast auto-dismiss
        const toast = document.getElementById('ptxToast');
        if (toast) {
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>
    @stack('scripts')
</body>
</html>
