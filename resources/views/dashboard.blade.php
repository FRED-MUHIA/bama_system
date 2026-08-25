@extends('layouts.app')
@section('title','Dashboard')
@php
    $hero = $industryHero ?? [];
    $heroActions = collect($hero['actions'] ?? []);
    $heroNodes = array_pad($hero['nodes'] ?? [], 6, 'DATA');
@endphp
@section('header-actions')
<div class="header-context-actions" aria-label="Dashboard quick actions">
    <span class="context-label">Quick actions</span>
    @foreach($heroActions as $action)
        <a class="btn {{ !empty($action['primary']) ? 'btn-success' : 'btn-outline-dark' }} btn-sm" href="{{ route($action['route'], $action['params'] ?? []) }}">
            <i class="bi {{ $action['icon'] }} me-1"></i><span>{{ $action['label'] }}</span>
        </a>
    @endforeach
</div>
@endsection
@section('content')
@php
    $cardIcons = [
        'Invoices' => 'bi-receipt',
        'Quotations' => 'bi-file-earmark-text',
        'Receipts' => 'bi-cash-coin',
        'POS Orders' => 'bi-shop',
        'Paid' => 'bi-check2-circle',
        'Unpaid' => 'bi-exclamation-circle',
        'Pending Payments' => 'bi-hourglass-split',
        'POS Revenue' => 'bi-graph-up-arrow',
        'Products' => 'bi-box-seam',
        'Projects' => 'bi-kanban',
        'Receivables' => 'bi-wallet2',
        'Collected' => 'bi-piggy-bank',
        'Profit' => 'bi-graph-up',
        'Tax Due' => 'bi-percent',
        'Supplier Due' => 'bi-truck',
    ];
    $moneyCards = ['Pending Payments', 'POS Revenue', 'Receivables', 'Collected', 'Profit', 'Tax Due', 'Supplier Due'];
    $projectsEnabled = \App\Models\Client::supportsCompanyStructure();
    $mobileMoney = fn ($value) => ($settings?->currency_code ?? 'KES').' '.number_format((float) $value, 2);
    $mobileTiles = [
        ['label' => 'To Receive', 'value' => $mobileMoney($cards['Pending Payments'] ?? $performance['outstanding']), 'tone' => 'receive', 'icon' => 'bi-arrow-down-left'],
        ['label' => 'To Give', 'value' => $mobileMoney($cards['Supplier Due'] ?? 0), 'tone' => 'give', 'icon' => 'bi-arrow-up-right'],
        ['label' => 'Sales', 'value' => $performance['revenueFormatted'], 'hint' => ucfirst($performance['period']), 'icon' => 'bi-chevron-right'],
        ['label' => 'Purchases', 'value' => $mobileMoney($cards['Supplier Due'] ?? 0), 'hint' => ucfirst($performance['period']), 'icon' => 'bi-chevron-right'],
        ['label' => 'Invoices', 'value' => $cards['Invoices'] ?? 0, 'hint' => 'Documents', 'icon' => 'bi-chevron-right'],
        ['label' => 'Total Balance', 'value' => $mobileMoney(($cards['Collected'] ?? 0) + ($cards['POS Revenue'] ?? 0)), 'hint' => 'Cash and bank', 'icon' => 'bi-chevron-right'],
    ];
    $mobileQuickActions = $heroActions->take(6)->values();
@endphp
<style>
    .modern-dashboard { color:#000; }
    .hero-panel { background:linear-gradient(135deg,#111827 0%,#102018 48%,#00A651 145%); border-radius:6px; padding:24px; color:#fff; box-shadow:0 22px 55px rgba(15,23,42,.18); position:relative; overflow:hidden; }
    .hero-panel:after { content:""; position:absolute; width:260px; height:260px; border-radius:50%; right:-90px; top:-120px; background:rgba(0,166,81,.24); }
    .hero-panel > * { position:relative; z-index:1; }
    .hero-kicker { font-size:.78rem; text-transform:uppercase; letter-spacing:.11em; color:#79D9A3; font-weight:800; }
    .hero-title { font-size:2rem; line-height:1.05; font-weight:900; letter-spacing:-.04em; margin:6px 0; }
    .hero-copy { color:#cbd5e1; max-width:620px; }
    .quick-actions .btn { border-radius:5px; padding:.65rem .95rem; font-weight:800; box-shadow:0 10px 24px rgba(0,0,0,.12); }
    .quick-actions .btn-outline-light { border-color:rgba(255,255,255,.36); color:#fff; }
    .quick-actions .btn-outline-light:hover { background:#fff; color:#111827; }
    .stat-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:0; margin:14px 0; background:#fff; border:1px solid #edf0f4; border-radius:5px; box-shadow:0 10px 24px rgba(15,23,42,.05); overflow:hidden; }
    .stat-card { background:#fff; border-right:1px solid #edf0f4; border-bottom:1px solid #edf0f4; border-radius:0; padding:14px 22px; min-height:84px; box-shadow:none; transition:background .16s ease; }
    .stat-card:hover { background:#EAF8F0; box-shadow:none; transform:none; }
    .stat-top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
    .stat-icon { display:none; }
    .stat-label { color:#64748b; font-size:.76rem; font-weight:700; line-height:1.15; }
    .stat-value { font-size:1.35rem; font-weight:900; letter-spacing:-.035em; margin-top:7px; white-space:nowrap; }
    .stat-foot { color:#16a34a; font-size:.68rem; font-weight:700; margin-top:7px; white-space:nowrap; }
    .panel-card { background:#fff; border:1px solid #edf0f4; border-radius:6px; box-shadow:0 14px 34px rgba(15,23,42,.06); }
    .panel-body { padding:22px; }
    .panel-title { font-weight:900; letter-spacing:-.025em; margin:0; }
    .analytics-layout { display:grid; grid-template-columns:360px minmax(0,1fr); gap:18px; align-items:stretch; }
    .revenue-card { background:linear-gradient(180deg,#071B12,#102018); color:#fff; border-radius:6px; padding:22px; height:100%; }
    .revenue-card .muted { color:#cbd5e1; }
    .revenue-value { font-size:2.35rem; font-weight:950; letter-spacing:-.06em; line-height:1; margin:10px 0; }
    .summary-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:16px; }
    .summary-tile { background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.10); border-radius:5px; padding:12px; }
    .summary-tile .label { color:#cbd5e1; font-size:.76rem; }
    .summary-tile .value { font-weight:900; font-size:1.05rem; }
    .chart-card { border:1px solid #edf0f4; border-radius:6px; padding:18px; min-height:360px; }
    .doc-table thead th { background:#f8fafc; color:#64748b; border-bottom:0; padding:14px; }
    .doc-table tbody td { padding:14px; border-color:#edf0f4; }
    .soft-link { text-decoration:none; font-weight:800; color:#000; }
    .soft-link:hover { color:#00A651; }
    .client-list { display:grid; gap:10px; }
    .client-item { display:flex; justify-content:space-between; align-items:center; padding:13px; border:1px solid #edf0f4; border-radius:5px; background:#fbfdff; }
    .status-pill { background:#EAF8F0; color:#007A3B; font-weight:800; }
    .industry-module-strip { display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;max-width:720px; }
    .industry-chip { padding:.42rem .62rem; }
    .industry-feature-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:14px; }
    .industry-feature-card { border:1px solid #edf0f4;border-radius:10px;padding:14px;background:#f8fafc;height:100%; }
    .dashboard-search { max-width:320px; position:relative; }
    .dashboard-search i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
    .dashboard-search input { padding-left:38px; border-radius:5px; border-color:#e5e7eb; }
    @media (max-width: 1500px) { .stat-grid { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    @media (max-width: 1200px) { .stat-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .analytics-layout { grid-template-columns:1fr; } }
    @media (max-width: 768px) { .stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .hero-title { font-size:1.65rem; } .quick-actions { width:100%; } .quick-actions .btn { flex:1 1 auto; } .summary-grid { grid-template-columns:1fr; } }
    @media (max-width: 480px) { .stat-grid { grid-template-columns:1fr; } }
</style>
<style>
    .modern-dashboard { --dash-green:#00A651;--dash-green-soft:#79D9A3;--dash-black:#071B12;--dash-ink:#000000; }
    .hero-panel { min-height:280px;background:linear-gradient(135deg,#111714 0%,#16231b 58%,#063b20 135%);border:1px solid rgba(0,166,81,.24);border-radius:16px;padding:clamp(22px,3vw,38px);box-shadow:none;display:grid;grid-template-columns:minmax(0,1.05fr) minmax(320px,.85fr);align-items:center;gap:24px;isolation:isolate; }
    .hero-panel::before { content:"";position:absolute;inset:0;z-index:-2;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:32px 32px;mask-image:linear-gradient(90deg,#000,transparent 88%); }
    .hero-panel::after { width:520px;height:520px;right:-190px;top:-250px;background:radial-gradient(circle,rgba(0,166,81,.2),transparent 68%);z-index:-1; }
    .hero-copy-block { max-width:690px; }
    .hero-kicker { display:inline-flex;align-items:center;gap:9px;color:#d2cec7;font-size:.68rem;letter-spacing:.18em;font-weight:700; }
    .hero-kicker::before { content:"";width:24px;height:1px;background:var(--dash-green); }
    .hero-title { font-family:var(--font-brand);font-size:clamp(1.9rem,3.1vw,3.25rem);font-weight:600;line-height:1;letter-spacing:0;max-width:640px;margin:12px 0; }
    .hero-title span { color:var(--dash-green); }
    .hero-copy { color:#aaa7a1;font-size:.92rem;line-height:1.6;max-width:540px;margin-bottom:18px; }
    .quick-actions .btn { box-shadow:none;border-radius:9px;padding:.55rem .78rem;font-size:.74rem; }
    .hero-art { position:relative;min-height:230px;display:grid;place-items:center; }
    .hero-art svg { width:min(100%,430px);height:auto;overflow:visible;filter:drop-shadow(0 18px 45px rgba(0,0,0,.35)); }
    .hero-art .orbit { transform-origin:250px 170px;animation:system-orbit 32s linear infinite; }
    .hero-art .signal { animation:signal-pulse 2.6s ease-in-out infinite; }
    .hero-art .signal:nth-child(2n) { animation-delay:.8s; }
    .hero-art-label { position:absolute;right:2%;bottom:3%;color:#777;font-size:.58rem;letter-spacing:.16em;text-transform:uppercase; }
    @keyframes system-orbit { to { transform:rotate(360deg); } }
    @keyframes signal-pulse { 50% { opacity:.25; } }
    .stat-grid { grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;background:transparent;border:0;border-radius:0;box-shadow:none;margin:18px 0 24px;overflow:visible; }
    .stat-card { position:relative;overflow:hidden;background:#fff;border:1px solid #e5e7eb !important;border-radius:8px;padding:18px;min-height:112px; }
    .stat-card::before { content:"";position:absolute;left:0;top:18px;width:2px;height:28px;background:var(--dash-green); }
    .stat-card:hover { background:#EAF8F0;transform:translateY(-2px);box-shadow:0 12px 32px rgba(31,31,31,.07); }
    .stat-label { text-transform:uppercase;letter-spacing:.09em;font-size:.64rem;color:#777; }
    .stat-value { font-family:var(--font-brand);font-weight:600;font-size:1.45rem;color:var(--dash-ink);margin-top:12px; }
    .stat-foot { color:#96928c;font-size:.63rem;font-weight:600; }
    .panel-card { background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 24px rgba(15,23,42,.06); }
    .panel-body { padding:clamp(18px,2.4vw,30px); }
    .panel-title { font-family:var(--font-brand);font-weight:600; }
    .analytics-layout { grid-template-columns:minmax(300px,390px) minmax(0,1fr);gap:16px; }
    .revenue-card { position:relative;overflow:hidden;background:#071B12;border:1px solid rgba(0,166,81,.22);border-radius:8px;padding:28px; }
    .revenue-card::after { content:"";position:absolute;width:190px;height:190px;border:1px solid rgba(0,166,81,.25);border-radius:50%;right:-95px;bottom:-95px;box-shadow:0 0 0 28px rgba(0,166,81,.04),0 0 0 56px rgba(0,166,81,.025); }
    .revenue-value { color:#fff;font-family:var(--font-brand);font-weight:600; }
    .summary-tile { border-radius:9px;background:rgba(255,255,255,.045);border-color:rgba(255,255,255,.08); }
    .chart-card { background:#fff;border-color:#e5e7eb;border-radius:8px; }
    .client-item { background:#fff;border-color:#e5e7eb;border-radius:8px; }
    .doc-table thead th { background:transparent; }
    @media(max-width:1300px){.hero-panel{grid-template-columns:1fr 340px}.stat-grid{grid-template-columns:repeat(4,1fr)}}
    @media(max-width:992px){.hero-panel{grid-template-columns:1fr;min-height:auto}.hero-art{min-height:190px}.hero-art svg{max-width:360px}.stat-grid{grid-template-columns:repeat(3,1fr)}.industry-module-strip{justify-content:flex-start}.industry-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:768px){.hero-panel{padding:22px 18px;border-radius:13px}.hero-title{font-size:2rem}.hero-art{opacity:.72;min-height:160px}.hero-art-label{display:none}.stat-grid{grid-template-columns:repeat(2,1fr)}.analytics-layout{grid-template-columns:1fr}.industry-panel .panel-body{padding:18px}.industry-panel .panel-title{font-size:1.35rem;line-height:1.12}.industry-panel p{font-size:.95rem;line-height:1.45}.industry-module-strip{flex-wrap:nowrap;overflow-x:auto;max-width:100%;padding-bottom:4px;scrollbar-width:none}.industry-module-strip::-webkit-scrollbar{display:none}.industry-chip{flex:0 0 auto;font-size:.66rem;padding:.38rem .55rem}.industry-feature-grid{grid-template-columns:1fr;gap:8px}.industry-feature-card{padding:11px 12px}.industry-feature-card .fw-bold{font-size:.9rem}}
    @media(max-width:480px){.stat-grid{grid-template-columns:1fr 1fr}.stat-card{padding:14px;min-height:100px}.stat-value{font-size:1.15rem}}
    @media(prefers-reduced-motion:reduce){.hero-art .orbit,.hero-art .signal{animation:none}}
</style>
<style>
    .mobile-app-home{display:none}
    @media(max-width:768px){
        .modern-dashboard{margin:-.35rem -.15rem 0;color:#111827}
        .mobile-app-home{display:block}
        .modern-dashboard > .industry-panel,
        .modern-dashboard > .hero-panel,
        .modern-dashboard > .stat-grid,
        .modern-dashboard > section.panel-card:not(.mobile-app-home),
        .modern-dashboard > .row{display:none!important}
        .mobile-industry-banner{border-radius:7px;background:linear-gradient(135deg,#073f24,#0c7a3e);color:#fff;padding:16px;margin-bottom:14px;min-height:82px;display:flex;align-items:flex-end;justify-content:space-between;gap:14px;overflow:hidden;position:relative}
        .mobile-industry-banner:after{content:"";position:absolute;right:-28px;top:-34px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.12)}
        .mobile-industry-banner strong{display:block;font-size:.95rem;line-height:1.15;position:relative;z-index:1}
        .mobile-industry-banner span{display:block;color:#c9f5d8;font-size:.72rem;font-weight:700;position:relative;z-index:1}
        .mobile-tile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-bottom:14px}
        .mobile-money-tile{min-height:78px;border:1px solid #edf0f4;border-radius:7px;background:#fff;padding:12px;box-shadow:0 4px 14px rgba(15,23,42,.04);display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
        .mobile-money-tile.receive{background:#eafaf1;border-color:#bfead1;color:#008342}
        .mobile-money-tile.give{background:#fff0f4;border-color:#f6c7d5;color:#d61f4c}
        .mobile-money-tile strong{display:block;font-size:.96rem;font-weight:800;color:inherit}
        .mobile-money-tile span{display:block;margin-top:8px;font-size:.68rem;color:#667085}
        .mobile-money-tile i{color:inherit;font-size:.9rem}
        .mobile-section-title{font-size:.92rem;font-weight:900;margin:10px 0}
        .mobile-action-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:14px}
        .mobile-action{min-height:92px;border:1px solid #edf0f4;border-radius:7px;background:#fff;text-decoration:none;color:#111827;display:grid;place-items:center;text-align:center;padding:12px 8px;box-shadow:0 4px 14px rgba(15,23,42,.04)}
        .mobile-action i{color:var(--tenant-primary,#00A651);font-size:1.15rem;margin-bottom:8px}
        .mobile-action span{display:block;font-size:.76rem;font-weight:750;line-height:1.25}
        .mobile-feed{border:1px solid #edf0f4;border-radius:8px;background:#fff;overflow:hidden}
        .mobile-feed-row{display:flex;align-items:center;gap:12px;padding:12px;border-bottom:1px solid #f1f3f7;text-decoration:none;color:#111827}
        .mobile-feed-row:last-child{border-bottom:0}
        .mobile-feed-icon{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;background:#eefbf3;color:var(--tenant-primary,#00A651);flex:0 0 34px}
        .mobile-feed-row strong{display:block;font-size:.8rem}
        .mobile-feed-row span{display:block;font-size:.68rem;color:#667085;margin-top:2px}
        .mobile-feed-row .bi-chevron-right{margin-left:auto;color:#98a2b3}
    }
</style>
<style>
    .industry-panel { overflow:hidden; }
    .industry-panel .panel-body { padding:clamp(18px,2vw,26px); }
    .industry-command-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(360px,520px); gap:18px; align-items:start; }
    .industry-command-main { min-width:0; }
    .industry-command-main p { max-width:760px; line-height:1.45; }
    .industry-module-board { background:#171717; border:1px solid rgba(0,166,81,.32); border-radius:8px; padding:14px; color:#fff; }
    .industry-module-head { display:flex; justify-content:space-between; align-items:center; gap:12px; padding-bottom:10px; margin-bottom:10px; border-bottom:1px solid rgba(255,255,255,.1); color:#d8d5cf; font-size:.66rem; font-weight:800; letter-spacing:.13em; text-transform:uppercase; }
    .industry-module-head strong { min-width:32px; height:32px; display:grid; place-items:center; border-radius:50%; background:#00A651; color:#fff; font-size:.8rem; letter-spacing:0; }
    .industry-module-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:7px; max-height:236px; overflow:auto; padding-right:2px; }
    .industry-chip { display:flex; align-items:center; gap:7px; min-height:34px; padding:7px 9px; border:1px solid rgba(255,255,255,.1); border-radius:7px; background:rgba(255,255,255,.045); color:#f7f3ed; font-size:.68rem; font-weight:800; line-height:1.12; letter-spacing:.03em; text-transform:uppercase; }
    .industry-chip i { color:#00A651; font-size:.82rem; flex:0 0 auto; }
    .industry-feature-grid { grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:18px; }
    .industry-feature-card { min-height:82px; border-color:#e3e0da; border-radius:8px; background:#faf8f4; padding:13px 14px; display:flex; flex-direction:column; justify-content:center; }
    .industry-feature-card .fw-bold { color:#171717; line-height:1.2; }
    @media(max-width:1280px){.industry-command-layout{grid-template-columns:1fr}.industry-module-board{order:2}.industry-module-grid{grid-template-columns:repeat(3,minmax(0,1fr));max-height:none}.industry-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:768px){.industry-command-layout{gap:14px}.industry-module-grid{display:flex;overflow-x:auto;scrollbar-width:none}.industry-module-grid::-webkit-scrollbar{display:none}.industry-chip{flex:0 0 178px}.industry-feature-grid{grid-template-columns:1fr}}
</style>

<div class="modern-dashboard">
    @unless($projectsEnabled)
        <div class="alert alert-warning">Projects are ready in code but hidden until the latest database migrations are run.</div>
    @endunless
    @php
        $industryModules = collect($industryDashboard['modules'] ?? []);
        $industryFeatures = collect($industryDashboard['dashboard_features'] ?? []);
    @endphp
    <section class="mobile-app-home panel-card">
        <div class="mobile-industry-banner">
            <div>
                <strong>{{ $industryDashboard['industry'] ?? $hero['kicker'] ?? 'Workspace' }}</strong>
                <span>{{ $industryDashboard['sub_industry'] ?? 'Business operations' }}</span>
            </div>
            <i class="bi bi-grid-1x2-fill"></i>
        </div>

        <div class="mobile-tile-grid">
            @foreach($mobileTiles as $tile)
                <a class="mobile-money-tile {{ $tile['tone'] ?? '' }}" href="{{ Route::has('finance.index') ? route('finance.index') : '#' }}">
                    <div>
                        <strong>{{ $tile['value'] }}</strong>
                        <span>{{ $tile['label'] }} @if(!empty($tile['hint']))({{ $tile['hint'] }})@endif</span>
                    </div>
                    <i class="bi {{ $tile['icon'] }}"></i>
                </a>
            @endforeach
        </div>

        <div class="mobile-section-title">Explore App</div>
        <div class="mobile-action-grid">
            @foreach($mobileQuickActions as $action)
                <a class="mobile-action" href="{{ route($action['route'], $action['params'] ?? []) }}">
                    <div>
                        <i class="bi {{ $action['icon'] }}"></i>
                        <span>{{ $action['label'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mobile-section-title">Recent Activity</div>
        <div class="mobile-feed">
            @forelse($recentInvoices->take(3) as $doc)
                <a class="mobile-feed-row" href="{{ route('invoices.show', $doc) }}">
                    <div class="mobile-feed-icon"><i class="bi bi-receipt"></i></div>
                    <div><strong>{{ $doc->invoice_number }}</strong><span>{{ $doc->client->name }} - {{ number_format($doc->total, 2) }}</span></div>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @empty
                <a class="mobile-feed-row" href="{{ route('invoices.create') }}">
                    <div class="mobile-feed-icon"><i class="bi bi-plus-circle"></i></div>
                    <div><strong>Create invoice</strong><span>No recent invoices yet</span></div>
                    <i class="bi bi-chevron-right"></i>
                </a>
            @endforelse
        </div>
    </section>
    @if(!empty($industryDashboard))
        <section class="panel-card industry-panel dashboard-visibility-anchor mb-3">
            <div class="panel-body">
                <div class="industry-command-layout">
                    <div class="industry-command-main">
                        <div class="text-muted small fw-semibold text-uppercase">Industry command center</div>
                        <h2 class="panel-title h4 mb-1">
                            {{ $industryDashboard['industry'] ?? 'Industry' }}
                            @if(!empty($industryDashboard['sub_industry']))
                                <span class="text-muted">/ {{ $industryDashboard['sub_industry'] }}</span>
                            @endif
                        </h2>
                        <p class="text-muted mb-0">{{ $industryDashboard['summary'] ?? '' }}</p>
                        <div class="industry-feature-grid">
                            @foreach($industryFeatures as $feature)
                                <div class="industry-feature-card">
                                    <div class="small text-muted fw-semibold text-uppercase">Dashboard feature</div>
                                    <div class="fw-bold mt-1">{{ $feature }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="industry-module-board">
                        <div class="industry-module-head">
                            <span>Enabled modules</span>
                            <strong>{{ number_format($industryModules->count()) }}</strong>
                        </div>
                        <div class="industry-module-grid">
                        @foreach($industryModules as $module)
                            <span class="industry-chip"><i class="bi bi-check2-circle"></i>{{ $module }}</span>
                        @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <section class="hero-panel {{ empty($industryDashboard) ? 'dashboard-visibility-anchor' : '' }} mb-3">
            <div class="hero-copy-block">
                <div class="hero-kicker">{{ $hero['kicker'] ?? 'OPERATIONAL INTELLIGENCE' }}</div>
                <div class="hero-title">{{ $hero['title'][0] ?? 'Connected systems.' }}<br><span>{{ $hero['title'][1] ?? 'Clear decisions.' }}</span></div>
                <div class="hero-copy">{{ $hero['copy'] ?? 'Monitor operations through one dependable workspace built for scale.' }}</div>
                <div class="quick-actions d-flex gap-2 flex-wrap">
                    @foreach($heroActions as $action)
                        <a class="btn {{ !empty($action['primary']) ? 'btn-success' : 'btn-outline-light' }}" href="{{ route($action['route'], $action['params'] ?? []) }}"><i class="bi {{ $action['icon'] }}"></i> {{ $action['label'] }}</a>
                    @endforeach
                </div>
            </div>
            <div class="hero-art" aria-label="{{ $hero['kicker'] ?? 'Connected systems' }}">
                <svg viewBox="0 0 500 340" role="img" aria-hidden="true">
                    <defs><radialGradient id="coreGlow"><stop offset="0" stop-color="#00A651" stop-opacity=".38"/><stop offset="1" stop-color="#00A651" stop-opacity="0"/></radialGradient></defs>
                    <circle cx="250" cy="170" r="116" fill="none" stroke="#00A651" stroke-opacity=".22" stroke-dasharray="3 8"/>
                    <g class="orbit"><ellipse cx="250" cy="170" rx="184" ry="84" fill="none" stroke="#fff" stroke-opacity=".13"/><ellipse cx="250" cy="170" rx="84" ry="154" fill="none" stroke="#00A651" stroke-opacity=".2" stroke-dasharray="6 9"/></g>
                    <circle cx="250" cy="170" r="82" fill="url(#coreGlow)"/><rect x="200" y="120" width="100" height="100" rx="16" fill="#202020" stroke="#00A651" stroke-width="1.3"/>
                    <path d="M221 150h58M221 170h38M221 190h48" stroke="#00A651" stroke-width="2" stroke-linecap="round"/><circle cx="278" cy="170" r="5" fill="#00A651"/>
                    <g fill="#171717" stroke="#00A651"><circle class="signal" cx="67" cy="170" r="8"/><circle class="signal" cx="433" cy="170" r="8"/><circle class="signal" cx="250" cy="17" r="8"/><circle class="signal" cx="250" cy="323" r="8"/><circle class="signal" cx="115" cy="85" r="6"/><circle class="signal" cx="385" cy="255" r="6"/></g>
                    <g stroke="#00A651" stroke-opacity=".45" stroke-dasharray="2 7"><path d="M75 170h125M300 170h125M250 25v95M250 220v95M120 88l84 46M296 206l84 46"/></g>
                    <g fill="#a5a5a5" font-family="Inter" font-size="9" letter-spacing="1"><text x="42" y="150">{{ $heroNodes[0] }}</text><text x="402" y="150">{{ $heroNodes[1] }}</text><text x="266" y="20">{{ $heroNodes[2] }}</text><text x="266" y="326">{{ $heroNodes[3] }}</text><text x="82" y="75">{{ $heroNodes[4] }}</text><text x="384" y="276">{{ $heroNodes[5] }}</text></g>
                </svg>
                <div class="hero-art-label">{{ $hero['status'] ?? 'SYSTEM STATUS · CONNECTED' }}</div>
            </div>
    </section>

    <section class="stat-grid">
        @foreach($cards as $label => $value)
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">{{ $label }}</div>
                        <div class="stat-value">{{ in_array($label, $moneyCards, true) ? number_format($value,2) : $value }}</div>
                    </div>
                    <div class="stat-icon"><i class="bi {{ $cardIcons[$label] ?? 'bi-dot' }}"></i></div>
                </div>
                <div class="stat-foot"><i class="bi bi-arrow-up-right"></i> Current activity</div>
            </div>
        @endforeach
    </section>

    <section class="panel-card mb-4">
        <div class="panel-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="text-muted small fw-semibold">Business activity</div>
                    <h2 class="panel-title h4">Performance Overview</h2>
                </div>
                <form method="get" action="{{ route('dashboard') }}" class="d-flex gap-2 flex-wrap align-items-end" id="performance-filter">
                    <select class="form-select form-select-sm" name="period" id="performance-period">
                        <option value="daily" @selected($performance['period']==='daily')>Daily</option>
                        <option value="weekly" @selected($performance['period']==='weekly')>Weekly</option>
                        <option value="monthly" @selected($performance['period']==='monthly')>Monthly</option>
                        <option value="yearly" @selected($performance['period']==='yearly')>Yearly</option>
                        <option value="custom" @selected($performance['period']==='custom')>Custom</option>
                    </select>
                    <input class="form-control form-control-sm custom-range-field {{ $performance['period'] === 'custom' ? '' : 'd-none' }}" type="date" name="from" value="{{ request('from', $performance['start']->format('Y-m-d')) }}">
                    <input class="form-control form-control-sm custom-range-field {{ $performance['period'] === 'custom' ? '' : 'd-none' }}" type="date" name="to" value="{{ request('to', $performance['end']->format('Y-m-d')) }}">
                    <button class="btn btn-sm btn-outline-success custom-range-field {{ $performance['period'] === 'custom' ? '' : 'd-none' }}">Apply</button>
                </form>
            </div>
            <div class="analytics-layout">
                <div class="revenue-card">
                    <div class="muted small">Collected POS payments</div>
                    <div class="revenue-value">{{ $performance['revenueFormatted'] }}</div>
                    <div class="muted small">{{ $performance['start']->format('d M Y') }} - {{ $performance['end']->format('d M Y') }}</div>
                    <div class="summary-grid">
                        <div class="summary-tile"><div class="label">Order value</div><div class="value">{{ $performance['orderValueFormatted'] }}</div></div>
                        <div class="summary-tile"><div class="label">Outstanding</div><div class="value">{{ $performance['outstandingFormatted'] }}</div></div>
                        <div class="summary-tile"><div class="label">Orders</div><div class="value">{{ $performance['orders'] }}</div></div>
                        <div class="summary-tile"><div class="label">Transactions</div><div class="value">{{ $performance['transactions'] }}</div></div>
                        <div class="summary-tile"><div class="label">Pending</div><div class="value">{{ $performance['pending'] }}</div></div>
                        <div class="summary-tile"><div class="label">Paid</div><div class="value">{{ $performance['paid'] }}</div></div>
                        <div class="summary-tile"><div class="label">Approved</div><div class="value">{{ $performance['approved'] }}</div></div>
                        <div class="summary-tile"><div class="label">Cancelled</div><div class="value">{{ $performance['cancelled'] }}</div></div>
                        <div class="summary-tile"><div class="label">Avg. order</div><div class="value">{{ $performance['averageOrderFormatted'] }}</div></div>
                    </div>
                    <a href="{{ route('pos-orders.report', ['from' => $performance['start']->format('Y-m-d'), 'to' => $performance['end']->format('Y-m-d')]) }}" class="btn btn-success btn-sm mt-3">Detailed analytics</a>
                </div>
                <div class="chart-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h6 fw-bold mb-0">Collected payments trend</h3>
                        <span class="text-muted small">{{ ucfirst($performance['period']) }}</span>
                    </div>
                    <div style="height:290px"><canvas id="performanceChart"></canvas></div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="panel-card h-100">
                <div class="panel-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="panel-title h5">Recent Documents</h2>
                        <div class="dashboard-search"><i class="bi bi-search"></i><input class="form-control form-control-sm" placeholder="Search documents"></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table doc-table align-middle mb-0">
                            <thead><tr><th>Type</th><th>Number</th><th>Client</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($recentInvoices as $doc)<tr><td>Invoice</td><td><a class="soft-link" href="{{ route('invoices.show',$doc) }}">{{ $doc->invoice_number }}</a></td><td>{{ $doc->client->name }}</td><td>{{ number_format($doc->total,2) }}</td><td><span class="status-pill">{{ $doc->payment_status }}</span></td></tr>@endforeach
                            @foreach($recentQuotations as $doc)<tr><td>Quotation</td><td><a class="soft-link" href="{{ route('quotations.show',$doc) }}">{{ $doc->quotation_number }}</a></td><td>{{ $doc->client->name }}</td><td>{{ number_format($doc->total,2) }}</td><td><span class="status-pill">{{ $doc->status }}</span></td></tr>@endforeach
                            @foreach($recentReceipts as $doc)<tr><td>Receipt</td><td><a class="soft-link" href="{{ route('receipts.show',$doc) }}">{{ $doc->receipt_number }}</a></td><td>{{ $doc->invoice->client->name }}</td><td>{{ number_format($doc->amount_paid,2) }}</td><td><span class="status-pill">paid</span></td></tr>@endforeach
                            @foreach($recentOrders as $doc)<tr><td>POS Order</td><td><a class="soft-link" href="{{ route('pos-orders.show',$doc) }}">{{ $doc->order_number }}</a></td><td>{{ $doc->client?->name ?: ($doc->customer_name ?: 'Walk-in') }}</td><td>{{ number_format($doc->total,2) }}</td><td><span class="status-pill">{{ $doc->status }}</span></td></tr>@endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="panel-card h-100">
                <div class="panel-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="panel-title h5">Recent Clients</h2>
                        <a class="btn btn-sm btn-outline-success" href="{{ route('clients.index') }}">View</a>
                    </div>
                    <div class="client-list">
                        @forelse($clients as $client)
                            <div class="client-item">
                                <div><a class="soft-link" href="{{ route('clients.show',$client) }}">{{ $client->name }}</a><div class="small text-muted">{{ $client->company_name ?: $client->email }}</div></div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        @empty
                            <p class="text-muted">No clients yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="panel-card mt-4">
        <div class="panel-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="panel-title h5">Top Products</h2>
                <a class="btn btn-sm btn-outline-success" href="{{ route('products.index') }}">Manage products</a>
            </div>
            <div class="table-responsive">
                <table class="table doc-table align-middle mb-0">
                    <thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
                    <tbody>
                    @forelse($topProducts as $product)
                        <tr><td>{{ $product->name }}</td><td>{{ $product->qty }}</td><td>{{ number_format($product->total,2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-muted">No product performance yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const hero = document.querySelector('.dashboard-visibility-anchor') || document.querySelector('.hero-panel');
    if (hero) {
        const updateHeader = (visible) => document.body.classList.toggle('dashboard-hero-condensed', !visible);
        if ('IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => updateHeader(entry.isIntersecting), { threshold: .08, rootMargin: '-76px 0px 0px' }).observe(hero);
        } else {
            const update = () => updateHeader(hero.getBoundingClientRect().bottom > 76);
            addEventListener('scroll', update, { passive: true }); update();
        }
    }
    const filter = document.getElementById('performance-filter');
    const period = document.getElementById('performance-period');
    const customFields = document.querySelectorAll('.custom-range-field');
    period?.addEventListener('change', () => {
        customFields.forEach((field) => field.classList.toggle('d-none', period.value !== 'custom'));
        if (period.value !== 'custom') filter.submit();
    });

    const values = @json($performance['chart']['values']);
    const labels = @json($performance['chart']['labels']);
    const formatter = new Intl.NumberFormat(@json(str_replace('_', '-', $performance['locale'])), { style: 'currency', currency: @json($performance['currencyCode']), maximumFractionDigits: 2 });
    const chartCanvas = document.getElementById('performanceChart');
    const chartIsVisible = () => chartCanvas && chartCanvas.offsetParent !== null && matchMedia('(min-width: 769px)').matches;
    let chartLoaded = false;
    let chartLoading;

    const loadChartJs = () => {
        if (window.Chart) return Promise.resolve();
        if (chartLoading) return chartLoading;

        chartLoading = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js';
            script.defer = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });

        return chartLoading;
    };

    const renderChart = () => {
        if (chartLoaded || !chartIsVisible()) return;
        chartLoaded = true;

        loadChartJs().then(() => {
            new Chart(chartCanvas, {
                type: 'bar',
                data: { labels, datasets: [{ data: values, backgroundColor: 'rgba(0,166,81,.22)', borderColor: '#00A651', borderWidth: 1.4, borderRadius: 8, maxBarThickness: 36 }] },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => formatter.format(context.parsed.y || 0) } } },
                    scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { callback: (value) => formatter.format(value) } } }
                }
            });
        }).catch(() => {
            chartLoaded = false;
        });
    };

    if (chartCanvas && 'IntersectionObserver' in window) {
        new IntersectionObserver(([entry], observer) => {
            if (!entry.isIntersecting) return;
            observer.disconnect();
            renderChart();
        }, { rootMargin: '160px 0px' }).observe(chartCanvas);
    } else {
        renderChart();
    }

    const desktopChartMedia = matchMedia('(min-width: 769px)');
    if (desktopChartMedia.addEventListener) {
        desktopChartMedia.addEventListener('change', renderChart);
    } else if (desktopChartMedia.addListener) {
        desktopChartMedia.addListener(renderChart);
    }
});
</script>
@endpush
@endsection
