<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BAMA Owner Console')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --owner-green:#00A651; --owner-ink:#101312; --owner-line:#dfe6e2; --owner-soft:#f6f8f7; }
        body { margin:0; background:var(--owner-soft); color:var(--owner-ink); font-family:Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .owner-shell { min-height:100vh; display:grid; grid-template-columns:260px minmax(0, 1fr); }
        .owner-sidebar { background:#07100c; color:#fff; padding:22px 16px; }
        .owner-brand { display:flex; align-items:center; gap:12px; margin-bottom:28px; font-weight:800; }
        .owner-logo { width:136px; height:auto; display:block; border-radius:6px; background:#fff; padding:4px; }
        .owner-nav { display:grid; gap:6px; }
        .owner-nav a { color:#b8c5bf; text-decoration:none; display:flex; align-items:center; gap:10px; min-height:42px; padding:0 12px; border-radius:8px; font-weight:700; }
        .owner-nav a.active,.owner-nav a:hover { color:#fff; background:rgba(0,166,81,.18); }
        .owner-main { min-width:0; }
        .owner-header { min-height:72px; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 28px; background:#fff; border-bottom:1px solid var(--owner-line); }
        .owner-title-eyebrow { color:#647067; font-size:.74rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
        .owner-content { padding:24px 28px 40px; }
        .owner-card { border:1px solid var(--owner-line); border-radius:8px; background:#fff; box-shadow:0 10px 28px rgba(16,19,18,.05); }
        .owner-metric { min-height:126px; padding:18px; }
        .owner-metric i { color:var(--owner-green); font-size:1.35rem; }
        .owner-metric strong { display:block; margin-top:14px; font-size:1.9rem; line-height:1; }
        .owner-table th { color:#66736b; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
        .btn-owner { background:var(--owner-green); border-color:var(--owner-green); color:#fff; font-weight:800; }
        .btn-owner:hover,.btn-owner:focus { background:#008f46; border-color:#008f46; color:#fff; }
        .badge-owner { background:#e7f7ee; color:#008f46; }
        @media (max-width: 900px) {
            .owner-shell { grid-template-columns:1fr; }
            .owner-sidebar { position:static; padding:16px; }
            .owner-nav { grid-template-columns:repeat(3, minmax(0, 1fr)); }
            .owner-nav a { justify-content:center; }
            .owner-header { align-items:flex-start; flex-direction:column; padding:18px; }
            .owner-content { padding:18px; }
        }
    </style>
</head>
<body>
    <div class="owner-shell">
        <aside class="owner-sidebar">
            <div class="owner-brand">
                <img class="owner-logo" src="{{ asset('images/bama-logo-cropped.png') }}" alt="BAMA">
                <div>
                    <small class="text-white-50">Owner Console</small>
                </div>
            </div>
            <nav class="owner-nav">
                <a href="{{ route('platform.dashboard') }}" class="{{ request()->routeIs('platform.dashboard') ? 'active' : '' }}"><i class="bi bi-grid"></i> Overview</a>
                <a href="{{ route('platform.tenants') }}" class="{{ request()->routeIs('platform.tenants') ? 'active' : '' }}"><i class="bi bi-buildings"></i> Clients</a>
                <a href="{{ route('platform.plans') }}" class="{{ request()->routeIs('platform.plans') ? 'active' : '' }}"><i class="bi bi-tags"></i> Pricing</a>
            </nav>
        </aside>
        <main class="owner-main">
            <header class="owner-header">
                <div>
                    <div class="owner-title-eyebrow">Platform Owner</div>
                    <h1 class="h3 mb-0">@yield('title', 'Overview')</h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-dark" href="{{ route('dashboard') }}"><i class="bi bi-window-sidebar"></i> Workspace</a>
                    <a class="btn btn-outline-dark" href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Profile</a>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-dark"><i class="bi bi-box-arrow-right"></i> Logout</button></form>
                </div>
            </header>
            <section class="owner-content">
                @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
                @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
                @if($errors->any()) <div class="alert alert-danger"><strong>Check the form:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
                @yield('content')
            </section>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
