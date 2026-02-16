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

    <link rel="icon" href="/icon.png" type="image/x-icon">

    {{-- Google Analytics --}}
    @php
        $gaId = app(\App\Services\Settings\PlatformSettingsService::class)->getString('GOOGLE_ANALYTICS_ID', '');
    @endphp
    @if($gaId)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $gaId }}');
    </script>
    @endif
</head>
<body class="ptx-admin">
    @php
        $settings = app(\App\Services\Settings\SettingsService::class);
        $simulatorEnabled = $settings->getBool('SIMULATOR_ENABLED', false);
    @endphp

    {{-- Impersonate Bar --}}
    @if(session('impersonator_id'))
    <div style="position:fixed;top:0;left:0;right:0;z-index:9999;background:linear-gradient(90deg,#ff4757,#ff6b81);color:#fff;text-align:center;padding:8px 16px;font-size:0.85rem;font-weight:600;font-family:var(--font-body);">
        Viewing as {{ Auth::user()->name }} ({{ Auth::user()->account_id }})
        <form method="POST" action="{{ route('admin.stop-impersonating') }}" style="display:inline;margin-left:12px;">
            @csrf
            <button type="submit" style="background:#fff;color:#ff4757;border:none;border-radius:6px;padding:3px 12px;font-size:0.8rem;font-weight:600;cursor:pointer;">Back to Admin</button>
        </form>
    </div>
    <div style="height:40px;"></div>
    @endif

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
            {{-- Commented out - AI costs included in subscription, only admin sees them --}}
            {{-- <a class="nav-link {{ request()->routeIs('ai-costs.*') ? 'active' : '' }}" href="{{ route('ai-costs.index') }}">
                <i class="bi bi-cash-coin"></i> AI Costs
            </a> --}}

            <div class="ptx-sidebar-divider"></div>
            <div class="nav-section-label">Settings</div>

            <a class="nav-link {{ request()->is('settings/profile') ? 'active' : '' }}" href="/settings/profile">
                <i class="bi bi-person-gear"></i> Profile
            </a>
            {{-- Polymarket Keys removed - not needed for simulation-only platform --}}
            <a class="nav-link {{ request()->is('settings/telegram') ? 'active' : '' }}" href="/settings/telegram">
                <i class="bi bi-telegram"></i> Telegram
            </a>
            <a class="nav-link {{ request()->is('settings/notifications') ? 'active' : '' }}" href="/settings/notifications">
                <i class="bi bi-bell"></i> Notifications
            </a>
            <a class="nav-link {{ request()->is('settings/security') ? 'active' : '' }}" href="{{ route('settings.security') }}">
                <i class="bi bi-shield-check"></i> Account Security
            </a>
            <a class="nav-link {{ request()->is('subscription*') ? 'active' : '' }}" href="/subscription">
                <i class="bi bi-credit-card"></i> Subscription
            </a>
            @if(in_array(auth()->user()->subscription_plan, ['advanced', 'lifetime'], true))
                <a class="nav-link {{ request()->is('contact') ? 'active' : '' }}" href="/contact">
                    <i class="bi bi-life-preserver"></i> Support Center
                </a>
            @endif

            @if(auth()->user()->isSuperAdmin())
                <div class="ptx-sidebar-divider"></div>
                <a class="nav-link text-warning" href="/admin">
                    <i class="bi bi-shield-lock"></i> Admin Panel
                </a>
            @endif
        </nav>

        <div class="ptx-sidebar-footer">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($simulatorEnabled)
                    <span class="ptx-badge-bot ptx-badge-on"><i class="bi bi-circle-fill" style="font-size:0.5rem"></i> Simulator ON</span>
                @else
                    <span class="ptx-badge-bot ptx-badge-off"><i class="bi bi-circle-fill" style="font-size:0.5rem"></i> Simulator OFF</span>
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
                    {{-- Commented out - Users don't need API keys for simulation --}}
                    {{-- <li><a class="dropdown-item" href="/settings/credentials"><i class="bi bi-key"></i> API Keys</a></li> --}}
                    <li><a class="dropdown-item" href="{{ route('settings.security') }}"><i class="bi bi-shield-check"></i> Account Security</a></li>
                    <li><a class="dropdown-item" href="/subscription"><i class="bi bi-credit-card"></i> Subscription</a></li>
                    @if(in_array(auth()->user()->subscription_plan, ['advanced', 'lifetime'], true))
                        <li><a class="dropdown-item" href="/contact"><i class="bi bi-life-preserver"></i> Support Center</a></li>
                    @endif
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

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-dismissible ptx-alert ptx-alert-success mx-4 mt-4 mb-0" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
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
        <button type="button" class="ptx-toast-close" onclick="closeToast()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    @if(session('analytics_events'))
    <script>
        (function () {
            var events = @json(session('analytics_events'));
            if (!Array.isArray(events)) return;

            window.dataLayer = window.dataLayer || [];

            events.forEach(function (item) {
                if (!item || !item.name) return;

                var params = (item.params && typeof item.params === 'object') ? item.params : {};
                window.dataLayer.push(Object.assign({ event: item.name }, params));

                if (typeof window.gtag === 'function') {
                    window.gtag('event', item.name, params);
                }
            });
        })();
    </script>
    @endif
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
        let toastTimeout;

        function closeToast() {
            if (toast) {
                clearTimeout(toastTimeout);
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }
        }

        if (toast) {
            requestAnimationFrame(() => toast.classList.add('show'));
            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 4000);
        }
    </script>
    @stack('scripts')
</body>
</html>
