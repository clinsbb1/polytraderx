<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $seoTitle = trim($__env->yieldContent('title', 'PolyTraderX — AI-Powered Polymarket Strategy Simulation Platform'));
        $seoDescription = trim($__env->yieldContent('meta_description', 'PolyTraderX is an AI-powered strategy lab for Polymarket crypto prediction markets. Design, simulate, and analyze strategies with real market data, without risking real money.'));
        $seoCanonical = trim($__env->yieldContent('canonical_url', url()->current()));
        $seoRobots = trim($__env->yieldContent('meta_robots', 'index, follow'));
        $seoOgType = trim($__env->yieldContent('og_type', 'website'));
        $seoImage = trim($__env->yieldContent('og_image', url('/favicon.ico')));
    @endphp

    <link rel="icon" href="/icon.png" type="image/x-icon">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="{{ $seoOgType }}">
    <meta property="og:site_name" content="PolyTraderX">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">

    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'PolyTraderX',
        'url' => rtrim(url('/'), '/'),
        'description' => $seoDescription,
        'inLanguage' => 'en-US',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 + Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Theme CSS (inline for simplicity — no Vite/build needed) --}}
    <style>{!! file_get_contents(resource_path('css/public.css')) !!}</style>

    @yield('extra-styles')

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
<body class="ptx-public">

    {{-- Navbar --}}
    <nav class="ptx-navbar" id="ptxNavbar">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <a class="navbar-brand d-flex align-items-center" href="/">
                    <i class="bi bi-graph-up-arrow me-2" style="color: var(--accent);"></i>
                    PolyTrader<span class="brand-x">X</span>
                </a>

                {{-- Mobile toggle --}}
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#ptxNavCollapse" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                {{-- Desktop nav --}}
                <div class="d-none d-lg-flex align-items-center gap-1">
                    <a class="nav-link" href="/#features">Features</a>
                    <a class="nav-link" href="/#how-ai-works">How It Works</a>
                    <a class="nav-link" href="/pricing">Pricing</a>
                    @auth
                        <a class="btn btn-ptx-nav ms-2" href="/dashboard">Dashboard</a>
                    @else
                        <a class="nav-link" href="/login">Login</a>
                        <a class="btn btn-ptx-nav ms-2" href="/register">Get Started Free</a>
                    @endauth
                </div>
            </div>

            {{-- Mobile collapse --}}
            <div class="collapse mt-3 d-lg-none" id="ptxNavCollapse">
                <div class="d-flex flex-column gap-1 pb-3">
                    <a class="nav-link" href="/#features">Features</a>
                    <a class="nav-link" href="/#how-ai-works">How It Works</a>
                    <a class="nav-link" href="/pricing">Pricing</a>
                    @auth
                        <a class="btn btn-ptx-nav mt-2" href="/dashboard">Dashboard</a>
                    @else
                        <a class="nav-link" href="/login">Login</a>
                        <a class="btn btn-ptx-nav mt-2" href="/register">Get Started Free</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Page Content --}}
    @yield('content')

    {{-- Footer --}}
    <footer class="ptx-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5><i class="bi bi-graph-up-arrow me-2" style="color: var(--accent);"></i>PolyTrader<span style="color: var(--accent);">X</span></h5>
                    <p class="small" style="color: var(--text-secondary); max-width: 300px;">
                        AI-powered automated trading for Polymarket crypto prediction markets. Late-minute certainty strategy with a 3-tier AI architecture.
                    </p>
                    <div class="ptx-social-icons mt-3">
                        <a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer" aria-label="Twitter / X"><i class="bi bi-twitter-x"></i></a>
                        <a href="https://t.me/+-DflYmPAmAowY2Q0" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
                        <a href="#" aria-label="Discord"><i class="bi bi-discord"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6>Product</h6>
                    <ul>
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/pricing">Pricing</a></li>
                        <li><a href="/#how-ai-works">How It Works</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6>Legal</h6>
                    <ul>
                        <li><a href="/terms">Terms of Service</a></li>
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/refund-policy">Refund Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6>Support</h6>
                    <ul>
                        <li><a href="/contact">Contact Us</a></li>
                        <li><a href="/login">Login</a></li>
                        <li><a href="/register">Get Started</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6>Connect</h6>
                    <ul>
                        <li><a href="https://x.com/polytraderx" target="_blank" rel="noopener noreferrer">Twitter / X</a></li>
                        <li><a href="https://t.me/+-DflYmPAmAowY2Q0" target="_blank" rel="noopener noreferrer">Telegram</a></li>
                        <li><a href="#">Discord</a></li>
                    </ul>
                </div>
            </div>
            <div class="ptx-footer-bottom">
                &copy; {{ date('Y') }} PolyTraderX. All rights reserved. Trading involves significant risk of loss.
            </div>
        </div>
    </footer>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

    {{-- Navbar scroll + Scroll reveal + Smooth scroll --}}
    <script>
    (function() {
        var nav = document.getElementById('ptxNavbar');
        if (nav) {
            var onScroll = function() {
                nav.classList.toggle('scrolled', window.scrollY > 40);
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        var reveals = document.querySelectorAll('.reveal');
        if (reveals.length && 'IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            reveals.forEach(function(el) { observer.observe(el); });
        }

        document.querySelectorAll('a[href^="#"]').forEach(function(a) {
            a.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    })();
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
