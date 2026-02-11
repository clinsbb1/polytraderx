<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PolyTraderX — Automated Polymarket Trading')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ptx-primary: #6366f1;
            --ptx-primary-dark: #4f46e5;
            --ptx-dark: #1e1b4b;
        }
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .navbar-brand { font-weight: 700; color: var(--ptx-primary) !important; font-size: 1.4rem; }
        .btn-ptx { background: var(--ptx-primary); color: #fff; border: none; }
        .btn-ptx:hover { background: var(--ptx-primary-dark); color: #fff; }
        .btn-ptx-outline { border: 2px solid var(--ptx-primary); color: var(--ptx-primary); background: transparent; }
        .btn-ptx-outline:hover { background: var(--ptx-primary); color: #fff; }
        .hero-section { background: linear-gradient(135deg, var(--ptx-dark) 0%, #312e81 100%); color: #fff; padding: 100px 0 80px; }
        .hero-section h1 { font-size: 3rem; font-weight: 800; }
        .feature-icon { width: 60px; height: 60px; border-radius: 12px; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--ptx-primary); }
        .pricing-card { border: 2px solid #e5e7eb; border-radius: 16px; transition: all 0.3s; }
        .pricing-card:hover, .pricing-card.featured { border-color: var(--ptx-primary); transform: translateY(-4px); box-shadow: 0 10px 40px rgba(99,102,241,0.15); }
        .pricing-card.featured { position: relative; }
        .pricing-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: var(--ptx-primary); color: #fff; padding: 2px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .footer { background: var(--ptx-dark); color: #9ca3af; padding: 40px 0; }
        .footer a { color: #9ca3af; text-decoration: none; }
        .footer a:hover { color: #fff; }
        @yield('extra-styles')
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="bi bi-graph-up-arrow me-2"></i>PolyTraderX</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="/#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="/pricing">Pricing</a></li>
                    @auth
                        <li class="nav-item ms-2"><a class="btn btn-ptx btn-sm" href="/dashboard">Dashboard</a></li>
                    @else
                        <li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
                        <li class="nav-item ms-2"><a class="btn btn-ptx btn-sm" href="/register">Get Started</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    @yield('content')

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5 class="text-white"><i class="bi bi-graph-up-arrow me-2"></i>PolyTraderX</h5>
                    <p class="small">Automated trading for Polymarket crypto prediction markets. AI-powered, late-minute certainty strategy.</p>
                </div>
                <div class="col-md-2 mb-3">
                    <h6 class="text-white">Product</h6>
                    <ul class="list-unstyled small">
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/pricing">Pricing</a></li>
                    </ul>
                </div>
                <div class="col-md-2 mb-3">
                    <h6 class="text-white">Legal</h6>
                    <ul class="list-unstyled small">
                        <li><a href="/terms">Terms of Service</a></li>
                        <li><a href="/privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-3">
                    <h6 class="text-white">Contact</h6>
                    <ul class="list-unstyled small">
                        <li><a href="/contact">Contact Us</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary">
            <p class="small text-center mb-0">&copy; {{ date('Y') }} PolyTraderX. All rights reserved. Trading involves risk.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
