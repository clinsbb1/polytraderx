<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — PolyTraderX Admin</title>
    <link rel="icon" href="/icon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --sa-primary: #6366f1; --sa-dark: #1e1b4b; --sa-sidebar: #312e81; }
        body { min-height: 100vh; }
        .sa-sidebar { width: 260px; min-height: 100vh; background: var(--sa-sidebar); position: fixed; top: 0; left: 0; z-index: 1000; }
        .sa-sidebar .nav-link { color: rgba(255,255,255,0.7); padding: 10px 20px; font-size: 0.9rem; border-radius: 8px; margin: 2px 12px; }
        .sa-sidebar .nav-link:hover, .sa-sidebar .nav-link.active { color: #fff; background: rgba(255,255,255,0.1); }
        .sa-sidebar .nav-link i { width: 24px; }
        .sa-main { margin-left: 260px; min-height: 100vh; background: #f8f9fa; }
        .sa-topbar { background: #fff; border-bottom: 1px solid #e5e7eb; padding: 12px 24px; }
        .sa-content { padding: 24px; }
        .sa-brand { color: #fff; font-weight: 700; font-size: 1.3rem; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .stat-card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    </style>
    @yield('extra-styles')

    {{-- Google Analytics, set please --}}
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
<body>
    <!-- Sidebar -->
    <nav class="sa-sidebar d-flex flex-column">
        <div class="sa-brand">
            <i class="bi bi-shield-lock me-2"></i>PTX Admin
        </div>
        <div class="nav flex-column mt-3">
            <a class="nav-link {{ request()->is('admin') ? 'active' : '' }}" href="/admin">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
            <a class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" href="/admin/users">
                <i class="bi bi-people me-2"></i>Users
            </a>
            <a class="nav-link {{ request()->is('admin/payments*') ? 'active' : '' }}" href="/admin/payments">
                <i class="bi bi-credit-card me-2"></i>Payments
            </a>
            <a class="nav-link {{ request()->is('admin/plans*') ? 'active' : '' }}" href="/admin/plans">
                <i class="bi bi-box me-2"></i>Plans
            </a>
            <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="/admin/settings">
                <i class="bi bi-gear me-2"></i>Platform Settings
            </a>
            <a class="nav-link {{ request()->is('admin/logs*') ? 'active' : '' }}" href="/admin/logs">
                <i class="bi bi-journal-text me-2"></i>Trade Logs
            </a>
            <a class="nav-link {{ request()->is('admin/ai-costs*') ? 'active' : '' }}" href="/admin/ai-costs">
                <i class="bi bi-cpu me-2"></i>AI Costs
            </a>
            <a class="nav-link {{ request()->is('admin/announcements*') ? 'active' : '' }}" href="/admin/announcements">
                <i class="bi bi-megaphone me-2"></i>Announcements
            </a>
        </div>
        <div class="mt-auto p-3 border-top border-secondary">
            <a class="nav-link text-white-50 small" href="{{ route('settings.security') }}"><i class="bi bi-shield-check me-2"></i>Account Security</a>
            <a class="nav-link text-white-50 small" href="/dashboard"><i class="bi bi-arrow-left me-2"></i>Back to Dashboard</a>
        </div>
    </nav>

    <!-- Main -->
    <div class="sa-main">
        <div class="sa-topbar d-flex justify-content-between align-items-center">
            <h5 class="mb-0">@yield('title', 'Admin')</h5>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
                </form>
            </div>
        </div>
        <div class="sa-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
    @yield('scripts')
</body>
</html>
