<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#00A651">
    <title>@yield('title', 'BAMA Admin')</title>
    <script>document.documentElement.dataset.theme=localStorage.getItem('bama-theme')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');</script>
    @if(!empty($tenantTheme?->faviconUrl()))<link rel="icon" href="{{ $tenantTheme->faviconUrl() }}">@endif
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Inter+Tight:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    <style>
        @font-face {
            font-family:'McQueen';
            src:local('McQueen SemiBold'),local('McQueen 600'),local('McQueen');
            font-weight:600;
            font-style:normal;
            font-display:swap;
        }
        :root { --font-brand:'McQueen','tt_normsregular','TT Norms','Inter Tight','Inter',ui-sans-serif,system-ui,sans-serif; --font-body:'tt_normsregular','TT Norms','Inter Tight','Inter',ui-sans-serif,system-ui,sans-serif; }
        h1,h2,h3,h4,h5,h6 {
            font-family:var(--font-brand) !important;
            font-weight:600 !important;
            font-optical-sizing:auto;
            font-variation-settings:'opsz' 28;
            letter-spacing:0;
            text-rendering:geometricPrecision;
            -webkit-font-smoothing:antialiased;
            -moz-osx-font-smoothing:grayscale;
        }
        body { background:#F7F8F5;font-family:var(--font-body);color:#000; }
        .sidebar { background:#071B12; min-height:100vh; }
        .sidebar a { color:#cbd5e1; text-decoration:none; display:flex; gap:.6rem; align-items:center; padding:.7rem 1rem; border-radius:.5rem; }
        .sidebar a:hover,.sidebar a.active { background:rgba(0,166,81,.16); color:#fff; }
        .card { border:0; border-radius:8px; box-shadow:0 8px 24px rgba(15,23,42,.06); }
        .table thead th { color:#667085; font-size:.78rem; text-transform:uppercase; letter-spacing:.03em; }
        .brand-mark { width:38px; height:38px; border-radius:8px; background:#00A651; color:#fff; display:grid; place-items:center; font-weight:800; }
        .status-pill { border-radius:999px; padding:.25rem .6rem; font-size:.78rem; background:#eef2ff; }
        .btn-warning { background:#00A651; border-color:#00A651; color:#fff; }
        .btn-warning:hover,.btn-warning:focus { background:#007A3B; border-color:#007A3B; color:#fff; }
        .btn-outline-warning { border-color:#00A651; color:#007A3B; }
        .btn-outline-warning:hover,.btn-outline-warning:focus { background:#00A651; border-color:#00A651; color:#fff; }
        .badge.bg-warning { background:#EAF8F0 !important; color:#007A3B !important; }
        .alert-warning { background:#EAF8F0; border-color:#BDE8CF; color:#007A3B; }
        .business-switcher { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:6px; padding:.75rem; margin-bottom:1rem; }
        .business-switcher label { color:#94a3b8; font-size:.72rem; font-weight:700; text-transform:uppercase; }
        .business-switcher .form-select,.business-switcher .form-control { border-radius:4px; font-size:.82rem; }
        .app-header { background:#fff; }
        .mobile-bottom-nav,.mobile-nav-backdrop,.mobile-overflow-sheet,.mobile-shell-backdrop,.mobile-shell-drawer { display:none; }
        .mobile-header-identity { display:none; }
        @media (max-width: 991px) { .sidebar { min-height:auto; } }
        @media (max-width: 991px) {
            body { background:#071B12; padding-bottom:calc(76px + env(safe-area-inset-bottom)); }
            .sidebar { display:none; }
            main.col-lg-10 { width:100%; max-width:100%; flex:0 0 100%; }
            main > header {
                min-height:54px;
                padding:.5rem .9rem !important;
                align-items:center !important;
                background:#071B12 !important;
                border-color:rgba(255,255,255,.08) !important;
                color:#fff;
            }
            main > header .text-muted { display:none; }
            main > header h1 {
                color:#fff;
                font-size:1.05rem;
                line-height:1.2;
                font-weight:800;
                max-width:72vw;
                overflow:hidden;
                text-overflow:ellipsis;
                white-space:nowrap;
            }
            main > header form { display:none; }
            main > section { padding:1rem .85rem 1.35rem !important; }
            .mobile-bottom-nav {
                position:fixed;
                left:0;
                right:0;
                bottom:0;
                z-index:1050;
                display:grid;
                grid-template-columns:repeat(5, minmax(0, 1fr));
                gap:4px;
                min-height:68px;
                padding:8px 9px calc(8px + env(safe-area-inset-bottom));
                background:rgba(16,24,40,.98);
                border:1px solid rgba(255,255,255,.08);
                border-bottom:0;
                border-radius:20px 20px 0 0;
                box-shadow:0 -14px 34px rgba(2,6,23,.34);
                backdrop-filter:blur(14px);
            }
            .mobile-bottom-nav a,.mobile-bottom-nav button {
                min-height:44px;
                border:0;
                background:transparent;
                color:#cbd5e1;
                text-decoration:none;
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                gap:3px;
                border-radius:12px;
                font-size:.64rem;
                font-weight:700;
                line-height:1;
                -webkit-tap-highlight-color:transparent;
            }
            .mobile-bottom-nav i { font-size:1.18rem; line-height:1; }
            .mobile-bottom-nav a.active,.mobile-bottom-nav button.active {
                color:#fff;
                background:rgba(0,166,81,.18);
            }
            .mobile-bottom-nav a.active i,.mobile-bottom-nav button.active i { color:#00A651; }
            .mobile-nav-backdrop {
                position:fixed;
                inset:0;
                z-index:1060;
                display:block;
                background:rgba(2,6,23,.58);
                opacity:0;
                pointer-events:none;
                transition:opacity .22s ease;
            }
            .mobile-shell-backdrop {
                position:fixed;
                inset:0;
                z-index:1080;
                display:block;
                background:rgba(2,6,23,.58);
                opacity:0;
                pointer-events:none;
                transition:opacity .22s ease;
            }
            .mobile-shell-drawer {
                position:fixed;
                top:0;
                bottom:0;
                left:0;
                z-index:1090;
                display:flex;
                flex-direction:column;
                width:min(88vw,360px);
                max-width:100%;
                padding:14px 14px calc(20px + env(safe-area-inset-bottom));
                background:#071B12;
                color:#fff;
                box-shadow:24px 0 60px rgba(2,6,23,.38);
                transform:translateX(-105%);
                transition:transform .24s ease;
                overflow-y:auto;
                overscroll-behavior:contain;
                -webkit-overflow-scrolling:touch;
            }
            .mobile-shell-drawer .drawer-head {
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:12px;
                margin-bottom:14px;
            }
            .mobile-shell-drawer .drawer-brand {
                display:flex;
                align-items:center;
                gap:10px;
                min-width:0;
            }
            .mobile-shell-drawer .drawer-brand strong,
            .mobile-shell-drawer .drawer-brand span {
                display:block;
                max-width:220px;
                overflow:hidden;
                text-overflow:ellipsis;
                white-space:nowrap;
            }
            .mobile-shell-drawer .drawer-brand span {
                color:#94a3b8;
                font-size:.72rem;
                font-weight:700;
            }
            .mobile-shell-drawer .drawer-close {
                width:42px;
                height:42px;
                border:0;
                border-radius:12px;
                background:rgba(255,255,255,.08);
                color:#fff;
                display:grid;
                place-items:center;
            }
            .mobile-shell-drawer .drawer-nav {
                display:grid;
                gap:4px;
            }
            .mobile-shell-drawer a,
            .mobile-shell-drawer button {
                min-height:46px;
                border-radius:12px;
                color:#e5e7eb;
                text-decoration:none;
                display:flex;
                align-items:center;
                gap:12px;
                padding:9px 10px;
                font-weight:750;
                border:0;
                background:transparent;
                text-align:left;
            }
            .mobile-shell-drawer a i,
            .mobile-shell-drawer button i {
                width:22px;
                color:#00A651;
                text-align:center;
                flex:0 0 22px;
            }
            .mobile-shell-drawer a.active {
                background:rgba(0,166,81,.18);
                color:#fff;
            }
            .mobile-shell-drawer .drawer-section-title {
                color:#94a3b8;
                font-size:.68rem;
                font-weight:800;
                letter-spacing:.12em;
                text-transform:uppercase;
                margin:16px 2px 7px;
            }
            .mobile-shell-drawer .business-switcher {
                margin-bottom:12px;
            }
            body.mobile-nav-open { overflow:hidden; }
            body.mobile-nav-open .mobile-shell-backdrop {
                opacity:1;
                pointer-events:auto;
            }
            body.mobile-nav-open .mobile-shell-drawer {
                transform:translateX(0);
            }
            .mobile-overflow-sheet {
                position:fixed;
                left:0;
                right:0;
                bottom:0;
                z-index:1070;
                display:block;
                max-height:calc(100dvh - 10px);
                overflow-y:auto;
                overscroll-behavior:contain;
                padding:10px 14px calc(18px + env(safe-area-inset-bottom));
                background:#071B12;
                color:#fff;
                border-radius:22px 22px 0 0;
                border:1px solid rgba(255,255,255,.08);
                border-bottom:0;
                box-shadow:0 -20px 44px rgba(2,6,23,.45);
                opacity:0;
                transform:translateY(105%);
                transition:transform .24s ease, opacity .18s ease;
                will-change:transform, opacity;
                -webkit-overflow-scrolling:touch;
            }
            .mobile-overflow-sheet .sheet-handle {
                width:42px;
                height:4px;
                border-radius:99px;
                background:#475467;
                margin:0 auto 14px;
            }
            .mobile-overflow-sheet .sheet-title {
                color:#94a3b8;
                font-size:.75rem;
                font-weight:700;
                text-transform:uppercase;
                margin:0 0 8px;
            }
            .mobile-overflow-sheet a,.mobile-overflow-sheet button {
                position:relative;
                z-index:1;
                width:100%;
                min-height:52px;
                border:0;
                border-radius:12px;
                background:transparent;
                color:#e5e7eb;
                text-decoration:none;
                display:flex;
                align-items:center;
                gap:12px;
                padding:0 12px;
                font-size:1rem;
                font-weight:700;
                cursor:pointer;
                touch-action:manipulation;
                pointer-events:auto;
                -webkit-tap-highlight-color:transparent;
            }
            .mobile-overflow-sheet a.active {
                color:#fff;
                background:rgba(0,166,81,.18);
            }
            .mobile-overflow-sheet i { color:#00A651; font-size:1.15rem; }
            body.mobile-overflow-open { overflow:hidden; }
            body.mobile-overflow-open .mobile-nav-backdrop {
                opacity:1;
                pointer-events:auto;
            }
            body.mobile-overflow-open .mobile-overflow-sheet {
                opacity:1;
                transform:translateY(0);
            }
        }
        /* BAMA engineering system */
        :root {
            --bama-ink:#1f1f1f;
            --bama-black:#171717;
            --bama-orange:#00A651;
            --bama-orange-dark:#007A3B;
            --bama-warm:#F7F8F5;
            --bama-paper:#ffffff;
            --bama-grey:#000000;
            --bama-line:#e5e7eb;
            --bama-radius:12px;
            --bama-scroll-thumb:#aaa59e;
            --bama-scroll-hover:#00A651;
        }
        html { background:var(--bama-warm); }
        * { scrollbar-width:thin;scrollbar-color:var(--bama-scroll-thumb) transparent; }
        *::-webkit-scrollbar { width:6px;height:6px; }
        *::-webkit-scrollbar-track { background:transparent; }
        *::-webkit-scrollbar-thumb { background:var(--bama-scroll-thumb);border-radius:999px; }
        *::-webkit-scrollbar-thumb:hover { background:var(--bama-scroll-hover); }
        *::-webkit-scrollbar-corner { background:transparent; }
        *::-webkit-scrollbar-button { display:none;width:0;height:0; }
        body {
            background:var(--bama-warm);
            color:var(--bama-ink);
            font-family:var(--font-body);
            font-size:.925rem;
            letter-spacing:0;
        }
        h1,h2,h3,h4,h5,h6,.brand-mark + strong {
            font-family:var(--font-brand);
            letter-spacing:0;
        }
        .container-fluid > .row { min-height:100vh; }
        .sidebar {
            position:relative;
            overflow:hidden;
            background:#071B12;
            border-right:1px solid rgba(255,255,255,.08);
        }
        .sidebar::before {
            content:"";
            position:absolute;
            inset:0;
            pointer-events:none;
            opacity:.28;
            background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px),radial-gradient(circle at 15% 8%,rgba(0,166,81,.22),transparent 25%);
            background-size:28px 28px,28px 28px,100% 100%;
        }
        .sidebar > * { position:relative; z-index:1; }
        .sidebar .brand-mark {
            width:40px;height:40px;border-radius:10px;
            background:var(--tenant-primary,var(--bama-orange));
            box-shadow:0 0 0 1px rgba(255,255,255,.08),0 10px 30px rgba(0,166,81,.18);
        }
        .sidebar .brand-mark + strong { font-size:.92rem; }
        .sidebar nav { gap:1px !important; padding-bottom:1rem; }
        .sidebar a {
            position:relative;
            color:#b9b9b9;
            min-height:38px;
            padding:.5rem .68rem;
            border:1px solid transparent;
            border-radius:8px;
            font-size:.8rem;
            font-weight:600;
            transition:background .18s ease,color .18s ease,border-color .18s ease,transform .18s ease;
        }
        .sidebar a i { color:#8d8d8d; font-size:.95rem; width:20px; text-align:center; flex:0 0 20px; }
        .sidebar a:hover { color:#fff; background:rgba(255,255,255,.045); transform:translateX(2px); }
        .sidebar a.active {
            color:#fff;
            background:color-mix(in srgb,var(--tenant-primary,var(--bama-orange)) 14%,transparent);
            border-color:color-mix(in srgb,var(--tenant-primary,var(--bama-orange)) 38%,transparent);
        }
        .sidebar a.active::before { content:"";position:absolute;left:-1px;top:8px;bottom:8px;width:2px;border-radius:4px;background:var(--tenant-primary,var(--bama-orange)); }
        .sidebar a.active i { color:var(--tenant-primary,var(--bama-orange)); }
        .sidebar-subnav { display:grid;gap:1px;margin:-.1rem 0 .2rem 1.44rem;padding-left:.55rem;border-left:1px solid rgba(255,255,255,.1); }
        .sidebar .sidebar-subnav a { min-height:30px;padding:.34rem .55rem;font-size:.73rem;color:#aeadad;border-radius:7px; }
        .sidebar .sidebar-subnav a i { width:16px;flex-basis:16px;font-size:.82rem; }
        .sidebar .sidebar-subnav a.active::before { left:-.6rem;top:7px;bottom:7px; }
        .business-switcher { background:rgba(255,255,255,.035);border-color:rgba(255,255,255,.08);border-radius:10px;padding:.7rem;margin-bottom:.75rem; }
        .business-switcher label { color:#8f8f8f;letter-spacing:.12em; }
        .business-switcher .form-control,.business-switcher .form-select { min-height:36px;background:#222;color:#f5f3ef;border-color:#3a3a3a;font-size:.8rem; }
        .business-switcher .form-control::placeholder { color:#8e8a84; opacity:1; }
        .business-switcher .btn { min-width:38px; min-height:36px; padding:.35rem .55rem; background:var(--tenant-primary,var(--bama-orange)); border-color:var(--tenant-primary,var(--bama-orange)); }
        .app-header {
            position:sticky;
            top:0;
            z-index:1040;
            min-height:76px;
            background:rgba(251,252,250,.94);
            border-color:var(--bama-line) !important;
            backdrop-filter:blur(12px);
            gap:.55rem;
        }
        .app-header > div:first-child { margin-right:auto; }
        .app-header-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:nowrap;justify-content:flex-end;min-width:0}
        .header-greeting{display:flex;align-items:center;gap:.55rem;border:1px solid #d8d5cf;background:rgba(255,255,255,.72);border-radius:9px;padding:.34rem .62rem;min-height:38px}
        .header-greeting .hello{display:block;color:#777;font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;line-height:1}
        .header-greeting .tenant-name{display:block;color:var(--bama-ink);font-size:.78rem;font-weight:800;line-height:1.2;max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .header-alert-btn{position:relative;width:38px;height:38px;display:inline-grid;place-items:center;padding:0}
        .header-alert-btn .header-badge{position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;border-radius:999px;background:#ef4444;color:#fff;border:2px solid #fff;font-size:.62rem;font-weight:800;display:grid;place-items:center;line-height:1;padding:0 4px}
        .header-notification-menu{width:min(360px,calc(100vw - 28px));border-radius:10px;border-color:#d8d5cf;box-shadow:0 18px 44px rgba(31,31,31,.16);padding:0;overflow:hidden}
        .header-notification-head{display:flex;justify-content:space-between;gap:1rem;align-items:center;padding:.85rem .95rem;border-bottom:1px solid #ebe8e2;background:#fff}
        .header-notification-head strong{font-size:.9rem}
        .header-notification-list{max-height:320px;overflow:auto}
        .header-notification-item{display:block;padding:.78rem .95rem;border-bottom:1px solid #f0eee9;color:var(--bama-ink);text-decoration:none}
        .header-notification-item:hover{background:#EAF8F0;color:var(--bama-ink)}
        .header-notification-item:last-child{border-bottom:0}
        .header-notification-item .notification-title{display:flex;justify-content:space-between;gap:.6rem;font-weight:800;font-size:.84rem}
        .header-notification-item .notification-body{color:#777;font-size:.76rem;margin-top:.18rem;line-height:1.35}
        .header-notification-foot{display:flex;gap:.5rem;padding:.75rem .95rem;border-top:1px solid #ebe8e2;background:#faf9f7}
        .header-context-actions{display:none;align-items:center;gap:6px;margin-right:8px;max-width:min(46vw,680px);overflow-x:auto;scrollbar-width:none;animation:header-actions-in .2s ease both}.header-context-actions::-webkit-scrollbar{display:none}.header-context-actions .btn{white-space:nowrap;flex:0 0 auto}.header-context-actions .context-label{color:#85817b;font-size:.58rem;font-weight:750;letter-spacing:.12em;text-transform:uppercase;margin-right:3px;white-space:nowrap;flex:0 0 auto}@keyframes header-actions-in{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}body.dashboard-hero-condensed .header-context-actions{display:flex}body.dashboard-hero-condensed .app-header{box-shadow:0 12px 34px rgba(31,31,31,.08);border-bottom-color:rgba(0,166,81,.3)!important}
        .app-header .text-muted { color:#777 !important;text-transform:uppercase;letter-spacing:.14em;font-size:.64rem !important;font-weight:700; }
        .app-header h1 { font-weight:600;color:var(--bama-ink); }
        main > section { max-width:1600px;margin-inline:auto;width:100%; }
        .card {
            background:var(--bama-paper);
            border:1px solid var(--bama-line);
            border-radius:var(--bama-radius);
            box-shadow:0 8px 30px rgba(31,31,31,.045);
        }
        .card .card-header { background:transparent;border-bottom:1px solid var(--bama-line);font-family:var(--font-brand);font-weight:600; }
        .btn { border-radius:9px;font-weight:650;font-size:.84rem;padding:.55rem .9rem;letter-spacing:-.01em; }
        .btn-sm { border-radius:8px;padding:.36rem .65rem;font-size:.76rem; }
        .btn-warning { background:var(--bama-orange);border-color:var(--bama-orange);color:#fff;box-shadow:0 6px 18px rgba(0,166,81,.16); }
        .btn-warning:hover,.btn-warning:focus { background:var(--bama-orange-dark);border-color:var(--bama-orange-dark);transform:translateY(-1px); }
        .btn-outline-warning { color:var(--bama-orange-dark);border-color:rgba(0,166,81,.55); }
        .btn-outline-warning:hover,.btn-outline-warning:focus { background:var(--bama-orange);border-color:var(--bama-orange); }
        .btn-outline-dark { border-color:#aaa;color:var(--bama-ink); }
        .form-label { margin-bottom:.4rem;color:#535353;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em; }
        .form-control,.form-select {
            min-height:42px;
            border-color:#d3d0ca;
            border-radius:9px;
            background-color:#fff;
            color:var(--bama-ink);
            font-size:.88rem;
            box-shadow:none;
        }
        .form-select { padding-right:2.35rem; }
        .form-control:focus,.form-select:focus { border-color:var(--bama-orange);box-shadow:0 0 0 3px rgba(0,166,81,.11); }
        textarea.form-control { min-height:110px; }
        .table { --bs-table-bg:transparent;--bs-table-hover-bg:rgba(0,166,81,.025);margin-bottom:0; }
        .table thead th { color:#686868;border-bottom:1px solid #cbc8c2;font-size:.67rem;letter-spacing:.1em;font-weight:750;padding:.8rem .75rem; }
        .table tbody td { border-color:#ebe8e2;padding:.78rem .75rem;vertical-align:middle; }
        .table tbody tr:last-child td { border-bottom:0; }
        .nav-tabs { border-bottom-color:#cfccc6;gap:.25rem; }
        .nav-tabs .nav-link { color:#717171;border:0;border-bottom:2px solid transparent;border-radius:0;font-size:.78rem;font-weight:700;padding:.7rem .85rem; }
        .nav-tabs .nav-link:hover { color:var(--bama-ink); }
        .nav-tabs .nav-link.active { color:var(--bama-orange-dark);background:transparent;border-bottom-color:var(--bama-orange); }
        .nav-pills .nav-link { color:#626262;border-radius:9px;font-weight:650;font-size:.8rem; }
        .nav-pills .nav-link.active { color:#fff;background:var(--bama-ink); }
        .badge,.status-pill { border-radius:999px;font-size:.65rem;font-weight:750;letter-spacing:.07em;text-transform:uppercase; }
        .alert { border-radius:10px;border-width:1px;box-shadow:none; }
        .alert-success { background:#eef7f0;border-color:#c9dfcd;color:#235c30; }
        .alert-warning { background:#fff4e9;border-color:#f1c49f;color:#803a0d; }
        .pagination { gap:.25rem; }
        .page-link { border-radius:7px !important;color:var(--bama-ink);border-color:#d8d5cf; }
        .active > .page-link { background:var(--bama-orange);border-color:var(--bama-orange); }
        a { color:var(--bama-orange-dark); }
        a:hover { color:#9f3c05; }
        @media (min-width:992px) {
            .sidebar { position:sticky;top:0;height:100vh;overflow-y:auto;width:226px;flex:0 0 226px;padding:.85rem .9rem !important; }
            .sidebar .sidebar-brand { margin-bottom:.85rem !important; }
            .sidebar { scrollbar-width:thin;scrollbar-color:#55514d transparent; }
            .sidebar::-webkit-scrollbar { width:6px; }
            .sidebar::-webkit-scrollbar-track { background:transparent; }
            .sidebar::-webkit-scrollbar-thumb { background:#55514d;border-radius:999px;border:1px solid var(--bama-black); }
            .sidebar::-webkit-scrollbar-thumb:hover { background:var(--bama-orange); }
            .sidebar::-webkit-scrollbar-button { display:none;width:0;height:0; }
            main.col-lg-10 { width:calc(100% - 226px);flex:0 0 calc(100% - 226px); }
        }
        @media (min-width:992px) and (max-height:820px) {
            .sidebar { padding:.65rem .78rem !important; }
            .sidebar .brand-mark { width:34px;height:34px;border-radius:9px;font-size:.85rem; }
            .sidebar .brand-mark + strong { font-size:.82rem; }
            .sidebar .sidebar-brand { margin-bottom:.6rem !important; }
            .business-switcher { padding:.55rem;margin-bottom:.6rem;border-radius:9px; }
            .business-switcher label { font-size:.62rem;margin-bottom:.25rem !important; }
            .business-switcher .form-control,.business-switcher .form-select { min-height:32px;font-size:.75rem;padding:.25rem .45rem; }
            .business-switcher form:first-child { margin-bottom:.45rem !important; }
            .business-switcher .btn { min-width:34px;min-height:32px;padding:.25rem .45rem; }
            .sidebar a { min-height:34px;padding:.42rem .58rem;font-size:.76rem; }
            .sidebar a i { font-size:.88rem; }
        }
        @media (max-width:768px) {
            body { background:var(--bama-warm); }
            main > header { background:rgba(23,23,23,.98) !important;border-color:#303030 !important; }
            main > section { padding:1rem .8rem 1.4rem !important; }
            .card { border-radius:11px; }
            .table-responsive { border-radius:9px; }
            .mobile-bottom-nav,.mobile-overflow-sheet { background:rgba(23,23,23,.98); }
            .mobile-bottom-nav a.active,.mobile-bottom-nav button.active,.mobile-overflow-sheet a.active { background:color-mix(in srgb,var(--tenant-primary,var(--bama-orange)) 16%,transparent); }
            .mobile-bottom-nav a.active i,.mobile-bottom-nav button.active i,.mobile-overflow-sheet i { color:var(--tenant-primary,var(--bama-orange)); }
            .nav-tabs { flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none; }
            .nav-tabs::-webkit-scrollbar { display:none; }
        }
        @media (max-width:991px) {
            body { background:var(--bama-warm); }
            main > header { background:rgba(23,23,23,.98) !important;border-color:#303030 !important; }
            main > header > a.btn { display:none; }
            main > section { padding:1rem .85rem 1.4rem !important; }
            .mobile-bottom-nav,.mobile-overflow-sheet { background:rgba(23,23,23,.98); }
            .mobile-bottom-nav a.active,.mobile-bottom-nav button.active,.mobile-overflow-sheet a.active { background:color-mix(in srgb,var(--tenant-primary,var(--bama-orange)) 16%,transparent); }
            .mobile-bottom-nav a.active i,.mobile-bottom-nav button.active i,.mobile-overflow-sheet i { color:var(--tenant-primary,var(--bama-orange)); }
            .table-responsive { -webkit-overflow-scrolling:touch; }
            .card:has(> table) { overflow-x:auto;-webkit-overflow-scrolling:touch; }
            .card > table { min-width:640px; }
            .table td .d-flex { flex-wrap:wrap; }
            .nav-tabs { flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none; }
            .nav-tabs::-webkit-scrollbar { display:none; }
            button,.btn,.form-control,.form-select { touch-action:manipulation; }
            body.dashboard-hero-condensed .app-header{flex-wrap:wrap;height:auto;padding-bottom:.55rem!important}.header-context-actions{order:3;width:100%;overflow-x:auto;padding-top:.45rem;margin:0;scrollbar-width:none}.header-context-actions::-webkit-scrollbar{display:none}.header-context-actions .context-label{display:none}.header-context-actions .btn{flex:0 0 auto;display:inline-grid;place-items:center;width:42px;height:42px;padding:0;border-color:#6b6b6b;color:#f7f3ed;background:rgba(255,255,255,.04)}.header-context-actions .btn.btn-success{background:var(--tenant-primary,#00A651);border-color:var(--tenant-primary,#00A651);color:#fff}.header-context-actions .btn:hover,.header-context-actions .btn:focus{border-color:var(--tenant-primary,#00A651);color:#fff;background:rgba(0,166,81,.18)}.header-context-actions .btn span{display:none}.header-context-actions .btn i{margin:0!important;font-size:1rem}.header-greeting{display:none}.app-header-actions{gap:.35rem}.app-header-actions .btn-sm:not(.theme-toggle){padding:.36rem .5rem}.app-header-actions .btn-sm span,.app-header-actions .btn-sm.profile-link{font-size:0}.app-header-actions .btn-sm.profile-link i{font-size:.9rem}
        }
        @media (max-width:991px) {
            body { background:#f7f8fb !important; padding-bottom:calc(70px + env(safe-area-inset-bottom)); }
            main > header {
                min-height:56px;
                background:#fff !important;
                border-color:#eef0f4 !important;
                color:#111827;
                box-shadow:none !important;
            }
            main > header .app-header-title { display:none; }
            .mobile-header-identity { display:flex;align-items:center;gap:10px;min-width:0; }
            .mobile-menu-button { width:42px;height:42px;border:0;background:#fff;color:#111827;display:grid;place-items:center;border-radius:12px;flex:0 0 42px; }
            .mobile-header-avatar { width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#f1f3f7;color:#111827;font-weight:800;flex:0 0 34px; }
            .mobile-header-name { color:#111827;font-size:.86rem;font-weight:800;line-height:1.1;max-width:48vw;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
            .app-header-actions { margin-left:auto;gap:.25rem; }
            .app-header-actions .header-greeting,
            .app-header-actions .header-context-actions,
            .app-header-actions .profile-link,
            .app-header-actions form,
            .app-header-actions .theme-toggle { display:none !important; }
            .header-alert-btn { width:40px;height:40px;border:0 !important;background:#fff !important;color:#111827 !important;box-shadow:none !important; }
            .header-alert-btn i { font-size:1.12rem; }
            .mobile-bottom-nav {
                min-height:64px;
                gap:2px;
                padding:7px 8px calc(7px + env(safe-area-inset-bottom));
                background:#fff;
                border:1px solid #e8ebf0;
                border-bottom:0;
                border-radius:0;
                box-shadow:0 -8px 24px rgba(15,23,42,.08);
                backdrop-filter:none;
            }
            .mobile-bottom-nav a,.mobile-bottom-nav button { color:#667085;border-radius:10px;font-size:.62rem;font-weight:700; }
            .mobile-bottom-nav a.active,.mobile-bottom-nav button.active { color:var(--tenant-primary,#00A651);background:transparent; }
            .mobile-bottom-nav a.active i,.mobile-bottom-nav button.active i { color:var(--tenant-primary,#00A651); }
            .mobile-overflow-sheet { background:#fff;color:#111827;border-color:#e8ebf0;box-shadow:0 -20px 44px rgba(15,23,42,.16);max-height:calc(100dvh - 24px);overflow-y:auto;-webkit-overflow-scrolling:touch; }
            .mobile-overflow-sheet .sheet-handle { background:#d0d5dd; }
            .mobile-overflow-sheet .sheet-title { color:#667085; }
            .mobile-overflow-sheet a,.mobile-overflow-sheet button { color:#111827; }
            .mobile-overflow-sheet a.active { color:var(--tenant-primary,#00A651);background:#eefbf3; }
            .mobile-overflow-sheet i { color:var(--tenant-primary,#00A651); }
            html:not([data-theme="dark"]) body,
            html:not([data-theme="dark"]) main,
            html:not([data-theme="dark"]) main > section,
            html:not([data-theme="dark"]) .card,
            html:not([data-theme="dark"]) .modal-content,
            html:not([data-theme="dark"]) .dropdown-menu,
            html:not([data-theme="dark"]) .list-group-item,
            html:not([data-theme="dark"]) .table,
            html:not([data-theme="dark"]) .table tbody td,
            html:not([data-theme="dark"]) .mobile-overflow-sheet,
            html:not([data-theme="dark"]) .mobile-overflow-sheet a,
            html:not([data-theme="dark"]) .mobile-overflow-sheet button,
            html:not([data-theme="dark"]) .mobile-bottom-nav a,
            html:not([data-theme="dark"]) .mobile-bottom-nav button {
                color:#111827 !important;
            }
            html:not([data-theme="dark"]) .text-muted,
            html:not([data-theme="dark"]) .form-text,
            html:not([data-theme="dark"]) small,
            html:not([data-theme="dark"]) .small,
            html:not([data-theme="dark"]) .table thead th,
            html:not([data-theme="dark"]) .form-label,
            html:not([data-theme="dark"]) .nav-tabs .nav-link,
            html:not([data-theme="dark"]) .nav-pills .nav-link,
            html:not([data-theme="dark"]) .dropdown-item,
            html:not([data-theme="dark"]) .page-link,
            html:not([data-theme="dark"]) .sheet-title,
            html:not([data-theme="dark"]) .mobile-header-name {
                color:#111827 !important;
            }
            html:not([data-theme="dark"]) .form-control,
            html:not([data-theme="dark"]) .form-select,
            html:not([data-theme="dark"]) .form-control:disabled,
            html:not([data-theme="dark"]) .form-select:disabled {
                color:#111827 !important;
                -webkit-text-fill-color:#111827;
                opacity:1;
            }
            html:not([data-theme="dark"]) .form-control::placeholder {
                color:#111827 !important;
                opacity:.82;
            }
        }
        @media (max-width:991px) {
            html,body {
                width:100%;
                max-width:100%;
                overflow-x:hidden;
                -webkit-text-size-adjust:100%;
                text-size-adjust:100%;
            }
            .container-fluid,
            .container-fluid > .row,
            main,
            main > section {
                width:100%;
                max-width:100%;
                min-width:0;
                overflow-x:hidden;
            }
            main > section > *,
            .card,
            .card-body,
            .panel-card,
            .pb-card,
            .modal-dialog,
            .dropdown-menu {
                max-width:100%;
                min-width:0;
            }
            input,
            select,
            textarea,
            .form-control,
            .form-select,
            .form-control-sm,
            .form-select-sm,
            .input-group .form-control,
            .input-group .form-select {
                font-size:16px !important;
                line-height:1.35;
            }
            .btn,
            .btn-sm {
                min-height:42px;
                display:inline-flex;
                align-items:center;
                justify-content:center;
                gap:.35rem;
                white-space:normal;
            }
            .input-group,
            .d-flex,
            .row {
                min-width:0;
            }
            .input-group {
                flex-wrap:wrap;
            }
            .input-group > .form-control,
            .input-group > .form-select {
                flex:1 1 180px;
                min-width:0;
            }
            .table-responsive,
            .card:has(> table),
            .doc-table-wrap {
                width:100%;
                max-width:100%;
                overflow-x:auto;
                -webkit-overflow-scrolling:touch;
            }
            .table {
                max-width:100%;
            }
            img,
            svg,
            canvas,
            video {
                max-width:100%;
                height:auto;
            }
            pre,
            code,
            .text-break {
                white-space:pre-wrap;
                overflow-wrap:anywhere;
            }
        }
        @media (prefers-reduced-motion:reduce) {
            *,*::before,*::after { scroll-behavior:auto !important;transition-duration:.01ms !important;animation-duration:.01ms !important;animation-iteration-count:1 !important; }
        }
        .theme-toggle{display:inline-grid;place-items:center;width:36px;height:36px;padding:0;border-radius:9px}.guest-theme-toggle{position:fixed;right:18px;top:18px;z-index:1080;background:rgba(255,253,250,.9);backdrop-filter:blur(10px)}
        html[data-theme="dark"]{color-scheme:dark;--bama-warm:#121212;--bama-paper:#1b1b1b;--bama-ink:#f2efe9;--bama-line:#353535;--bama-scroll-thumb:#55514d}html[data-theme="dark"] body{background:#121212;color:#e9e6e0}html[data-theme="dark"] .app-header{background:rgba(23,23,23,.95);border-color:#353535!important}html[data-theme="dark"] .app-header h1,html[data-theme="dark"] h1,html[data-theme="dark"] h2,html[data-theme="dark"] h3,html[data-theme="dark"] h4,html[data-theme="dark"] h5,html[data-theme="dark"] h6{color:#f2efe9}html[data-theme="dark"] .app-header .text-muted,html[data-theme="dark"] .text-muted{color:#9c9891!important}html[data-theme="dark"] .header-greeting,html[data-theme="dark"] .header-notification-head,html[data-theme="dark"] .header-notification-menu{background:#1b1b1b;border-color:#353535}html[data-theme="dark"] .header-notification-item{border-color:#2d2d2d;color:#f2efe9}html[data-theme="dark"] .header-notification-item:hover{background:#24211f}html[data-theme="dark"] .header-notification-foot{background:#181818;border-color:#353535}html[data-theme="dark"] .header-alert-btn .header-badge{border-color:#1b1b1b}
        html[data-theme="dark"] .card,html[data-theme="dark"] .panel-card,html[data-theme="dark"] .stat-card,html[data-theme="dark"] .login-card{background:#1b1b1b!important;border-color:#383838!important;color:#e9e6e0;box-shadow:none}html[data-theme="dark"] .form-control,html[data-theme="dark"] .form-select{background-color:#202020;color:#f1eee8;border-color:#444}html[data-theme="dark"] .form-control::placeholder{color:#777}html[data-theme="dark"] .form-label{color:#aaa69f}
        html[data-theme="dark"] .table{--bs-table-color:#dedad3;--bs-table-border-color:#383838;--bs-table-hover-color:#fff;--bs-table-hover-bg:rgba(0,166,81,.055);color:#dedad3}html[data-theme="dark"] .table thead th{color:#9e9a93;border-color:#444;background:transparent}html[data-theme="dark"] .table tbody td{border-color:#333}html[data-theme="dark"] .nav-tabs{border-color:#3b3b3b}html[data-theme="dark"] .nav-tabs .nav-link{color:#aaa69f}html[data-theme="dark"] .nav-tabs .nav-link.active{color:#79D9A3}html[data-theme="dark"] .nav-pills{background:#242424}html[data-theme="dark"] .nav-pills .nav-link{color:#aaa69f}html[data-theme="dark"] .nav-pills .nav-link.active{background:#00A651;color:#fff}
        html[data-theme="dark"] .btn-outline-dark{color:#e8e4de;border-color:#5a5a5a}html[data-theme="dark"] .btn-outline-dark:hover{background:#eee;color:#171717}html[data-theme="dark"] .page-link{background:#202020;color:#ddd;border-color:#444}html[data-theme="dark"] .client-item,html[data-theme="dark"] .chart-card{background:#202020!important;border-color:#383838!important}html[data-theme="dark"] .stat-value,html[data-theme="dark"] .soft-link{color:#f2efe9}
        html[data-theme="dark"] .login-auth-panel{background:#121212}html[data-theme="dark"] .login-auth-intro,html[data-theme="dark"] .login-auth-label{color:#aaa69f}html[data-theme="dark"] .login-card .nav-pills{background:#242424}html[data-theme="dark"] .password-toggle{color:#aaa}html[data-theme="dark"] .password-toggle:hover{background:#303030;color:#fff}html[data-theme="dark"] .guest-theme-toggle{background:rgba(30,30,30,.9);color:#eee;border-color:#555}html[data-theme="dark"] .alert-success{background:#15251a;border-color:#31593a;color:#b9dfc0}html[data-theme="dark"] .alert-warning{background:#2a2118;border-color:#654525;color:#f2c79e}html[data-theme="dark"] .alert-danger{background:#2b1818;border-color:#663737;color:#efb5b5}
    </style>
    <style>:root { {!! $tenantCssVariables ?? '--tenant-primary:#00A651; --tenant-secondary:#000000; --tenant-accent:#00A651;' !!} }</style>
    @vite('resources/css/app.css')
</head>
<body>
@php
    $currentUser = auth()->user();
    $isClientPortal = $currentUser?->role === 'client_portal';
    $mainColumnClass = $currentUser && ! $isClientPortal ? 'col-lg-10' : 'col-12';
    $sidebarBrandName = $activeTenant?->name ?? $activeBusiness?->name ?? 'BAMA';
    $activeIndustryLabel = \Illuminate\Support\Str::headline($activeBusiness?->industry ?: $activeTenant?->industry ?: 'Workspace');
@endphp
<div class="container-fluid">
    <div class="row">
        @if($currentUser && ! $isClientPortal)
            <aside class="col-lg-2 sidebar p-3">
                <div class="sidebar-brand d-flex align-items-center gap-2 text-white mb-3">
                    @if(!empty($tenantTheme?->logoUrl()))
                        <img src="{{ $tenantTheme->logoUrl() }}" alt="{{ $activeTenant?->name ?? 'Tenant' }}" style="width:42px;height:42px;object-fit:contain;border-radius:8px;background:#fff;">
                    @elseif(strcasecmp($sidebarBrandName, 'BAMA') === 0)
                        <img src="{{ asset('images/bama-solutions-02.png') }}" alt="Bama Solutions" style="width:124px;height:auto;object-fit:contain;border-radius:6px;background:#fff;padding:4px;">
                    @else
                        <div class="brand-mark">{{ strtoupper(substr($sidebarBrandName, 0, 1)) }}{{ strtoupper(substr(strstr($sidebarBrandName, ' ') ?: 'A', 1, 1)) }}</div>
                    @endif
                    @unless(strcasecmp($sidebarBrandName, 'BAMA') === 0 && empty($tenantTheme?->logoUrl()))
                        <strong>{{ $sidebarBrandName }}</strong>
                    @endunless
                </div>
                <div class="business-switcher">
                    <form method="post" action="{{ route('businesses.switch') }}" class="mb-2">
                        @csrf
                        <label class="form-label mb-1">Business</label>
                        <select class="form-select form-select-sm" name="business_id" onchange="this.form.submit()">
                            @foreach($businesses as $business)
                                <option value="{{ $business->id }}" @selected($activeBusiness?->id === $business->id)>{{ $business->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    <div class="d-flex gap-1" title="Adding more businesses is coming soon. Each profile manages one business for now.">
                        <input class="form-control form-control-sm" value="Coming soon" disabled aria-label="Add business coming soon">
                        <button class="btn btn-secondary btn-sm" type="button" disabled title="Coming soon"><i class="bi bi-lock"></i></button>
                    </div>
                </div>
                <nav class="d-grid gap-1">
                    @foreach($platformMenu ?? [] as $item)
                        @php
                            $routeParams = $item['params'] ?? [];
                            $children = collect($item['children'] ?? []);

                            if (! empty($item['section'])) {
                                $routeParams = array_merge($routeParams, ['section' => $item['section']]);
                            }

                            $activePatterns = collect($item['active_routes'] ?? [])
                                ->push(str_replace('.index', '.*', $item['route']))
                                ->push($item['route'])
                                ->filter()
                                ->unique()
                                ->values();

                            $isActive = $activePatterns->contains(fn ($pattern) => request()->routeIs($pattern));

                            if (! empty($item['section'])) {
                                $isActive = request()->routeIs($item['route'])
                                    && request()->query('section', 'dashboard') === $item['section'];
                            }

                            $childIsActive = $children->contains(function ($child) {
                                $patterns = collect($child['active_routes'] ?? [])
                                    ->push(str_replace('.index', '.*', $child['route']))
                                    ->push($child['route'])
                                    ->filter()
                                    ->unique()
                                    ->values();

                                return $patterns->contains(fn ($pattern) => request()->routeIs($pattern));
                            });

                            $isActive = $isActive || $childIsActive;
                        @endphp
                        <a href="{{ route($item['route'], $routeParams) }}" class="{{ $isActive ? 'active' : '' }}"><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
                        @if($children->isNotEmpty())
                            <div class="sidebar-subnav">
                                @foreach($children as $child)
                                    @php
                                        $childRouteParams = $child['params'] ?? [];
                                        $childActivePatterns = collect($child['active_routes'] ?? [])
                                            ->push(str_replace('.index', '.*', $child['route']))
                                            ->push($child['route'])
                                            ->filter()
                                            ->unique()
                                            ->values();
                                        $isChildActive = $childActivePatterns->contains(fn ($pattern) => request()->routeIs($pattern));
                                    @endphp
                                    <a href="{{ route($child['route'], $childRouteParams) }}" class="{{ $isChildActive ? 'active' : '' }}"><i class="bi {{ $child['icon'] }}"></i> {{ $child['label'] }}</a>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </nav>
            </aside>
        @endif
        <main class="{{ $mainColumnClass }} p-0">
            @if($currentUser)
                <header class="app-header border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                    <div class="mobile-header-identity">
                        @if(! $isClientPortal)
                            <button type="button" class="mobile-menu-button" id="mobile-shell-open" aria-controls="mobile-shell-drawer" aria-expanded="false" aria-label="Open navigation"><i class="bi bi-list"></i></button>
                        @endif
                        <div class="mobile-header-avatar">{{ strtoupper(substr($currentUser->name ?? 'U', 0, 1)) }}</div>
                        <div class="mobile-header-name">{{ $activeBusiness?->name ?? $activeTenant?->name ?? $currentUser->name }}</div>
                    </div>
                    <div class="app-header-title">
                        <div class="text-muted small">Admin dashboard</div>
                        <h1 class="h4 mb-0">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="app-header-actions">
                        @yield('header-actions')
                        @if(! $isClientPortal)
                            <div class="header-greeting">
                                <i class="bi bi-person-circle text-success"></i>
                                <span>
                                    <span class="hello">Hello</span>
                                    <span class="tenant-name">{{ $activeBusiness?->name ?? $activeTenant?->name ?? $currentUser->name }}</span>
                                </span>
                            </div>
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-dark btn-sm header-alert-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Message alerts">
                                    <i class="bi bi-bell"></i>
                                    @if(($headerUnreadCount ?? 0) > 0)
                                        <span class="header-badge">{{ ($headerUnreadCount ?? 0) > 99 ? '99+' : $headerUnreadCount }}</span>
                                    @endif
                                </button>
                                <div class="dropdown-menu dropdown-menu-end header-notification-menu">
                                    <div class="header-notification-head">
                                        <div>
                                            <strong>{{ 'Messages & Alerts' }}</strong>
                                            <div class="text-muted small">{{ number_format($headerMessageCount ?? 0) }} unread message{{ ($headerMessageCount ?? 0) === 1 ? '' : 's' }}</div>
                                        </div>
                                        <span class="badge bg-danger">{{ number_format($headerUnreadCount ?? 0) }}</span>
                                    </div>
                                    <div class="header-notification-list">
                                        @forelse(($headerNotifications ?? collect()) as $notification)
                                            @php
                                                $notificationUrl = $notification->action_url ?: (Route::has('communication.center') ? route('communication.center') : '#');
                                                $notificationIcon = in_array($notification->notification_type, ['Message', 'Mention'], true) ? 'bi-chat-dots' : 'bi-bell';
                                            @endphp
                                            <a class="header-notification-item" href="{{ $notificationUrl }}">
                                                <div class="notification-title">
                                                    <span><i class="bi {{ $notificationIcon }} me-1 text-success"></i>{{ $notification->title }}</span>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans(null, true) }}</small>
                                                </div>
                                                @if($notification->body)
                                                    <div class="notification-body">{{ \Illuminate\Support\Str::limit($notification->body, 95) }}</div>
                                                @endif
                                            </a>
                                        @empty
                                            <div class="p-3 text-muted">No new messages or alerts.</div>
                                        @endforelse
                                    </div>
                                    <div class="header-notification-foot">
                                        @if(Route::has('communication.center'))
                                            <a class="btn btn-success btn-sm flex-fill" href="{{ route('communication.center') }}"><i class="bi bi-chat-dots"></i> Messaging</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        <button type="button" class="btn btn-outline-dark btn-sm theme-toggle" data-theme-toggle aria-label="Switch colour theme"><i class="bi bi-moon-stars"></i></button>
                        <a class="btn btn-outline-dark btn-sm profile-link" href="{{route('profile.edit')}}"><i class="bi bi-person"></i> <span>My Profile</span></a>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-dark btn-sm"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></button></form>
                    </div>
                </header>
            @endif
            <section class="p-4">
                @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
                @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif
                @if($errors->any()) <div class="alert alert-danger"><strong>Check the form:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
                @if($currentUser && ! $isClientPortal && !empty($subscriptionBillingState['message']) && !request()->routeIs('billing.*'))
                    <div class="alert {{ ($subscriptionBillingState['state'] ?? '') === 'locked' ? 'alert-danger' : 'alert-warning' }} d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>{{ $subscriptionBillingState['message'] }}</strong>
                            @if(!empty($subscriptionBillingState['expires_at']))
                                <div class="small">Expires: {{ $subscriptionBillingState['expires_at']->format('d M Y') }} · Grace ends: {{ $subscriptionBillingState['grace_ends_at']?->format('d M Y') }}</div>
                            @endif
                        </div>
                        @if(Route::has('billing.index'))
                            <a class="btn btn-sm btn-warning" href="{{ route('billing.index') }}"><i class="bi bi-credit-card"></i> Renew</a>
                        @endif
                    </div>
                @endif
                @yield('content')
            </section>
        </main>
    </div>
</div>
@if(! $currentUser)<button type="button" class="btn btn-outline-dark theme-toggle guest-theme-toggle" data-theme-toggle aria-label="Switch colour theme"><i class="bi bi-moon-stars"></i></button>@endif
@if($currentUser && ! $isClientPortal)
    <div class="mobile-shell-backdrop" id="mobile-shell-backdrop" aria-hidden="true"></div>
    <aside class="mobile-shell-drawer" id="mobile-shell-drawer" role="dialog" aria-modal="true" aria-label="Mobile navigation" aria-hidden="true" tabindex="-1">
        <div class="drawer-head">
            <div class="drawer-brand">
                @if(!empty($tenantTheme?->logoUrl()))
                    <img src="{{ $tenantTheme->logoUrl() }}" alt="{{ $activeTenant?->name ?? 'Tenant' }}" style="width:42px;height:42px;object-fit:contain;border-radius:8px;background:#fff;">
                @elseif(strcasecmp($sidebarBrandName, 'BAMA') === 0)
                    <img src="{{ asset('images/bama-solutions-02.png') }}" alt="Bama Solutions" style="width:118px;height:auto;object-fit:contain;border-radius:6px;background:#fff;padding:4px;">
                @else
                    <div class="brand-mark">{{ strtoupper(substr($sidebarBrandName, 0, 1)) }}{{ strtoupper(substr(strstr($sidebarBrandName, ' ') ?: 'A', 1, 1)) }}</div>
                @endif
                <div class="min-w-0">
                    <strong>{{ $sidebarBrandName }}</strong>
                    <span>{{ $activeIndustryLabel }}</span>
                </div>
            </div>
            <button type="button" class="drawer-close" id="mobile-shell-close" aria-label="Close navigation"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="business-switcher">
            <form method="post" action="{{ route('businesses.switch') }}">
                @csrf
                <label class="form-label mb-1">Business</label>
                <select class="form-select form-select-sm" name="business_id" onchange="this.form.submit()">
                    @foreach($businesses as $business)
                        <option value="{{ $business->id }}" @selected($activeBusiness?->id === $business->id)>{{ $business->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="drawer-section-title">Navigation</div>
        <nav class="drawer-nav">
            @foreach($platformMenu ?? [] as $item)
                @php
                    $routeParams = $item['params'] ?? [];
                    $children = collect($item['children'] ?? []);

                    if (! empty($item['section'])) {
                        $routeParams = array_merge($routeParams, ['section' => $item['section']]);
                    }

                    $activePatterns = collect($item['active_routes'] ?? [])
                        ->push(str_replace('.index', '.*', $item['route']))
                        ->push($item['route'])
                        ->filter()
                        ->unique()
                        ->values();

                    $isActive = $activePatterns->contains(fn ($pattern) => request()->routeIs($pattern));

                    if (! empty($item['section'])) {
                        $isActive = request()->routeIs($item['route'])
                            && request()->query('section', 'dashboard') === $item['section'];
                    }

                    $childIsActive = $children->contains(function ($child) {
                        $patterns = collect($child['active_routes'] ?? [])
                            ->push(str_replace('.index', '.*', $child['route']))
                            ->push($child['route'])
                            ->filter()
                            ->unique()
                            ->values();

                        return $patterns->contains(fn ($pattern) => request()->routeIs($pattern));
                    });

                    $isActive = $isActive || $childIsActive;
                @endphp
                <a href="{{ route($item['route'], $routeParams) }}" class="{{ $isActive ? 'active' : '' }}"><i class="bi {{ $item['icon'] }}"></i><span class="text-truncate">{{ $item['label'] }}</span></a>
                @foreach($children as $child)
                    @php
                        $childRouteParams = $child['params'] ?? [];
                        $childActivePatterns = collect($child['active_routes'] ?? [])
                            ->push(str_replace('.index', '.*', $child['route']))
                            ->push($child['route'])
                            ->filter()
                            ->unique()
                            ->values();
                        $isChildActive = $childActivePatterns->contains(fn ($pattern) => request()->routeIs($pattern));
                    @endphp
                    <a href="{{ route($child['route'], $childRouteParams) }}" class="{{ $isChildActive ? 'active' : '' }}"><i class="bi {{ $child['icon'] }}"></i><span class="text-truncate">{{ $child['label'] }}</span></a>
                @endforeach
            @endforeach
        </nav>
        <div class="drawer-section-title">Workspace</div>
        <nav class="drawer-nav">
            @if(Route::has('communication.center'))
                <a href="{{ route('communication.center') }}" class="{{ request()->routeIs('communication.*') ? 'active' : '' }}"><i class="bi bi-chat-dots"></i><span>Messages</span></a>
            @endif
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person"></i><span>Profile</span></a>
            <button type="button" data-theme-toggle><i class="bi bi-moon-stars"></i><span>Switch theme</span></button>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit"><i class="bi bi-box-arrow-right"></i><span>Logout</span></button></form>
        </nav>
    </aside>
    @php
        $genericMobileItems = [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'bi-grid-fill', 'aria' => 'Dashboard'],
            ['label' => 'Products', 'route' => 'products.index', 'match' => 'products.*', 'icon' => 'bi-box-seam'],
            ['label' => 'Orders', 'route' => 'pos-orders.index', 'match' => 'pos-orders.*', 'icon' => 'bi-shop', 'aria' => 'POS Orders'],
            ['label' => 'Clients', 'route' => 'clients.index', 'match' => 'clients.*', 'icon' => 'bi-people'],
        ];

        $genericOverflowItems = [
            ['label' => 'Projects', 'route' => 'projects.index', 'match' => 'projects.*', 'icon' => 'bi-kanban', 'condition' => \App\Models\Client::supportsCompanyStructure()],
            ['label' => 'Procurement', 'route' => 'erp.procurement', 'match' => 'erp.procurement', 'icon' => 'bi-truck', 'condition' => \App\Support\SchemaCache::hasTable('suppliers') && \App\Support\SchemaCache::hasTable('purchase_orders')],
            ['label' => 'ERP', 'route' => 'erp.reports', 'match' => 'erp.*', 'icon' => 'bi-bar-chart', 'condition' => \App\Support\SchemaCache::hasTable('project_costs')],
            ['label' => 'Cost Accounting', 'route' => 'accounting.index', 'match' => 'accounting.*', 'icon' => 'bi-diagram-3', 'condition' => \App\Support\SchemaCache::hasTable('departments')],
            ['label' => 'Finance', 'route' => 'finance.index', 'match' => 'finance.*', 'icon' => 'bi-bank', 'condition' => \App\Support\SchemaCache::hasTable('journal_entries')],
            ['label' => 'Letters', 'route' => 'letters.index', 'match' => 'letters.*', 'icon' => 'bi-envelope-paper', 'condition' => \App\Support\SchemaCache::hasTable('letters')],
            ['label' => 'Quotations', 'route' => 'quotations.index', 'match' => 'quotations.*', 'icon' => 'bi-file-earmark-text'],
            ['label' => 'Invoices', 'route' => 'invoices.index', 'match' => 'invoices.*', 'icon' => 'bi-receipt'],
            ['label' => 'Receipts', 'route' => 'receipts.index', 'match' => 'receipts.*', 'icon' => 'bi-cash-coin'],
        ];

        $fitnessMobileItems = [
            ['label' => 'Dashboard', 'route' => 'fitness.dashboard', 'match' => 'fitness.dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Memberships', 'route' => 'fitness.memberships.index', 'match' => 'fitness.memberships.*', 'icon' => 'bi-card-checklist'],
            ['label' => 'Members', 'route' => 'fitness.members.index', 'match' => 'fitness.members.*', 'icon' => 'bi-people'],
            ['label' => 'Check-In', 'route' => 'fitness.check-in.index', 'match' => 'fitness.check-in.*', 'icon' => 'bi-box-arrow-in-right'],
            ['label' => 'Trainers', 'route' => 'fitness.trainers.index', 'match' => 'fitness.trainers.*', 'icon' => 'bi-person-workspace'],
            ['label' => 'Attendance', 'route' => 'fitness.attendance.index', 'match' => 'fitness.attendance.*', 'icon' => 'bi-qr-code-scan'],
            ['label' => 'Classes', 'route' => 'fitness.classes.index', 'match' => 'fitness.classes.*', 'icon' => 'bi-calendar-week'],
            ['label' => 'Programs', 'route' => 'fitness.programs.index', 'match' => 'fitness.programs.*', 'icon' => 'bi-clipboard2-pulse'],
            ['label' => 'Exercises', 'route' => 'fitness.exercises.index', 'match' => 'fitness.exercises.*', 'icon' => 'bi-list-check'],
            ['label' => 'Health', 'route' => 'fitness.health-profiles.index', 'match' => 'fitness.health-profiles.*', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Assessments', 'route' => 'fitness.assessments.index', 'match' => 'fitness.assessments.*', 'icon' => 'bi-graph-up-arrow'],
            ['label' => 'PT', 'route' => 'fitness.personal-training.index', 'match' => 'fitness.personal-training.*', 'icon' => 'bi-person-arms-up'],
            ['label' => 'Nutrition', 'route' => 'fitness.nutrition.index', 'match' => 'fitness.nutrition.*', 'icon' => 'bi-egg-fried'],
            ['label' => 'Challenges', 'route' => 'fitness.challenges.index', 'match' => 'fitness.challenges.*', 'icon' => 'bi-trophy'],
            ['label' => 'Equipment', 'route' => 'fitness.equipment.index', 'match' => 'fitness.equipment.*', 'icon' => 'bi-tools'],
            ['label' => 'Inventory', 'route' => 'products.index', 'match' => 'products.*', 'icon' => 'bi-box-seam'],
            ['label' => 'Payments', 'route' => 'finance.index', 'match' => 'finance.*', 'icon' => 'bi-cash-coin'],
            ['label' => 'Reports', 'route' => 'fitness.reports.index', 'match' => 'fitness.reports.*', 'icon' => 'bi-bar-chart'],
        ];

        $hospitalityMobileItems = [
            ['label' => 'Dashboard', 'route' => 'hospitality.dashboard', 'match' => 'hospitality.dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Reservations', 'route' => 'hospitality.reservations.index', 'match' => 'hospitality.reservations.*', 'icon' => 'bi-calendar-check'],
            ['label' => 'Rooms', 'route' => 'hospitality.rooms.index', 'match' => 'hospitality.rooms.*', 'icon' => 'bi-door-open'],
            ['label' => 'Guests', 'route' => 'hospitality.guests.index', 'match' => 'hospitality.guests.*', 'icon' => 'bi-person-heart'],
            ['label' => 'Front Desk', 'route' => 'hospitality.front-desk.index', 'match' => 'hospitality.front-desk.*', 'icon' => 'bi-building-check'],
            ['label' => 'Check-In', 'route' => 'hospitality.check-ins.index', 'match' => 'hospitality.check-ins.*', 'icon' => 'bi-box-arrow-in-right'],
            ['label' => 'Check-Out', 'route' => 'hospitality.check-outs.index', 'match' => 'hospitality.check-outs.*', 'icon' => 'bi-box-arrow-right'],
            ['label' => 'Housekeeping', 'route' => 'hospitality.housekeeping.index', 'match' => 'hospitality.housekeeping.*', 'icon' => 'bi-brush'],
            ['label' => 'Maintenance', 'route' => 'hospitality.maintenance.index', 'match' => 'hospitality.maintenance.*', 'icon' => 'bi-tools'],
            ['label' => 'Restaurant', 'route' => 'hospitality.restaurant.index', 'match' => 'hospitality.restaurant.*', 'icon' => 'bi-cup-hot'],
            ['label' => 'Events', 'route' => 'hospitality.events.index', 'match' => 'hospitality.events.*', 'icon' => 'bi-calendar-event'],
            ['label' => 'Stock', 'route' => 'products.index', 'match' => 'products.*', 'icon' => 'bi-box-seam'],
            ['label' => 'Suppliers', 'route' => 'hospitality.suppliers.index', 'match' => 'hospitality.suppliers.*', 'icon' => 'bi-truck'],
            ['label' => 'Procurement', 'route' => 'erp.procurement', 'match' => 'erp.procurement', 'icon' => 'bi-cart-check'],
            ['label' => 'Billing', 'route' => 'finance.index', 'match' => 'finance.*', 'icon' => 'bi-bank'],
            ['label' => 'Reports', 'route' => 'hospitality.reports.index', 'match' => 'hospitality.reports.*', 'icon' => 'bi-bar-chart'],
        ];

        $agricultureMobileItems = [
            ['label' => 'Dashboard', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'dashboard', 'icon' => 'bi-speedometer2'],
            ['label' => 'Farms', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'farms', 'icon' => 'bi-flower1'],
            ['label' => 'Crops', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'crops', 'icon' => 'bi-calendar2-week'],
            ['label' => 'Harvest', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'harvest', 'icon' => 'bi-basket'],
            ['label' => 'Livestock', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'livestock', 'icon' => 'bi-heart-pulse'],
            ['label' => 'Inputs', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'inputs', 'icon' => 'bi-droplet'],
            ['label' => 'Equipment', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'equipment', 'icon' => 'bi-truck'],
            ['label' => 'Irrigation', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'irrigation', 'icon' => 'bi-water'],
            ['label' => 'Workers', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'workers', 'icon' => 'bi-people'],
            ['label' => 'Storage', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'storage', 'icon' => 'bi-box-seam'],
            ['label' => 'Sales', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'sales', 'icon' => 'bi-cash-stack'],
            ['label' => 'Compliance', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'compliance', 'icon' => 'bi-shield-check'],
            ['label' => 'Documents', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'documents', 'icon' => 'bi-folder2-open'],
            ['label' => 'Finance', 'route' => 'agriculture.dashboard', 'match' => 'agriculture.dashboard', 'section' => 'finance', 'icon' => 'bi-bank'],
            ['label' => 'Reports', 'route' => 'agriculture.reports.index', 'match' => 'agriculture.reports.*', 'icon' => 'bi-bar-chart'],
        ];

        $utilityOverflowItems = [
            ['label' => 'Settings', 'route' => 'settings.edit', 'match' => 'settings.*', 'icon' => 'bi-gear'],
            ['label' => 'My Profile', 'route' => 'profile.edit', 'match' => 'profile.*', 'icon' => 'bi-person'],
            ['label' => 'Administration', 'route' => 'administration.index', 'match' => 'administration.*', 'icon' => 'bi-shield-lock', 'condition' => \App\Support\SchemaCache::hasTable('iam_roles') && $currentUser->hasPermission('administration.view')],
        ];

        $mobileContextLabel = 'More';
        $mobileContextItems = $genericMobileItems;
        $mobileOverflowItems = $genericOverflowItems;
        $activeIndustrySlug = \Illuminate\Support\Str::of($activeBusiness?->industry ?: $activeTenant?->industry ?: '')->snake(' ')->slug('-')->toString();
        $isFitnessContext = request()->routeIs('fitness.*') || in_array($activeIndustrySlug, ['fitness', 'fitness-gym', 'fitness-and-gym'], true);
        $isHospitalityContext = request()->routeIs('hospitality.*') || $activeIndustrySlug === 'hospitality';
        $isAgricultureContext = request()->routeIs('agriculture.*') || $activeIndustrySlug === 'agriculture';

        if ($isFitnessContext) {
            $mobileContextLabel = 'Fitness & Gym';
            $mobileContextItems = $fitnessMobileItems;
            $mobileOverflowItems = array_slice($fitnessMobileItems, 4);
        } elseif ($isHospitalityContext) {
            $mobileContextLabel = 'Hospitality';
            $mobileContextItems = $hospitalityMobileItems;
            $mobileOverflowItems = array_slice($hospitalityMobileItems, 4);
        } elseif ($isAgricultureContext) {
            $mobileContextLabel = 'Agriculture';
            $mobileContextItems = $agricultureMobileItems;
            $mobileOverflowItems = array_slice($agricultureMobileItems, 4);
        }

        $sidebarMobileItems = collect($platformMenu ?? [])
            ->flatMap(function ($item) {
                $parent = [collect($item)->except('children')->all()];

                return array_merge($parent, $item['children'] ?? []);
            })
            ->filter(fn ($item) => ! empty($item['route']))
            ->map(function ($item) {
                $route = $item['route'];
                $match = collect($item['active_routes'] ?? [])
                    ->push(str_replace('.index', '.*', $route))
                    ->push($route)
                    ->filter()
                    ->first();

                return [
                    'label' => ($item['label'] ?? '') === 'Dashboard' ? 'Home' : ($item['label'] ?? 'Open'),
                    'route' => $route,
                    'match' => $match,
                    'section' => $item['section'] ?? null,
                    'params' => $item['params'] ?? [],
                    'icon' => $item['icon'] ?? 'bi-grid',
                ];
            })
            ->unique(fn ($item) => $item['route'].':'.($item['section'] ?? md5(json_encode($item['params'] ?? []))))
            ->values();

        if ($sidebarMobileItems->isNotEmpty()) {
            $priority = ['Home', 'Dashboard', 'Transactions', 'Orders', 'POS Orders', 'Finance', 'Clients', 'Tenants', 'Guests', 'Members', 'Products', 'Inventory', 'Rooms', 'Properties', 'Projects'];
            $mobileContextLabel = \Illuminate\Support\Str::headline($activeIndustrySlug ?: 'Workspace');
            $mobileContextItems = $sidebarMobileItems
                ->sortBy(fn ($item) => ($index = array_search($item['label'], $priority, true)) === false ? 99 : $index)
                ->values()
                ->all();
            $mobileOverflowItems = $sidebarMobileItems
                ->reject(fn ($item) => in_array($item['label'], collect($mobileContextItems)->take(4)->pluck('label')->all(), true))
                ->values()
                ->all();
        }

        $mobileItemAvailable = fn ($item) => \Illuminate\Support\Facades\Route::has($item['route']) && ($item['condition'] ?? true);
        $mobileRouteMatches = fn ($item) => ! empty($item['section'])
            ? (request()->routeIs($item['route']) && request()->query('section', 'dashboard') === $item['section'])
            : (request()->routeIs($item['match']) || request()->routeIs($item['route']));
        $mobileRouteParams = fn ($item) => ! empty($item['section'])
            ? array_merge($item['params'] ?? [], ['section' => $item['section']])
            : ($item['params'] ?? []);
        $mobilePrimaryItems = collect($mobileContextItems)->filter($mobileItemAvailable)->take(4)->values();
        $mobileOverflowItems = collect($mobileOverflowItems)
            ->merge($utilityOverflowItems)
            ->filter($mobileItemAvailable)
            ->unique(fn ($item) => $item['route'].':'.($item['section'] ?? md5(json_encode($item['params'] ?? []))))
            ->values();
        $mobileMoreActive = $mobileOverflowItems->contains($mobileRouteMatches);
    @endphp
    <nav class="mobile-bottom-nav" aria-label="Mobile navigation">
        @foreach($mobilePrimaryItems as $item)
            <a href="{{ route($item['route'], $mobileRouteParams($item)) }}" class="{{ $mobileRouteMatches($item) ? 'active' : '' }}" @if(!empty($item['aria'])) aria-label="{{ $item['aria'] }}" @endif><i class="bi {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
        @endforeach
        <button type="button" id="mobile-overflow-open" class="{{ $mobileMoreActive ? 'active' : '' }}" aria-controls="mobile-overflow-menu" aria-expanded="false"><i class="bi bi-three-dots"></i><span>More</span></button>
    </nav>
    <div class="mobile-nav-backdrop" id="mobile-overflow-backdrop" aria-hidden="true"></div>
    <div class="mobile-overflow-sheet" id="mobile-overflow-menu" role="dialog" aria-modal="true" aria-label="More navigation" aria-hidden="true">
        <div class="sheet-handle"></div>
        <div class="sheet-title">{{ $mobileContextLabel }}</div>
        @foreach($mobileOverflowItems as $item)
            <a href="{{ route($item['route'], $mobileRouteParams($item)) }}" class="{{ $mobileRouteMatches($item) ? 'active' : '' }}"><i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
        @endforeach
        <button type="button" data-theme-toggle><i class="bi bi-moon-stars"></i> <span>Switch theme</span></button>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </div>
@endif
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const buttons=document.querySelectorAll('[data-theme-toggle]');
    const render=()=>{const dark=document.documentElement.dataset.theme==='dark';buttons.forEach(button=>{button.querySelector('i').className=dark?'bi bi-sun':'bi bi-moon-stars';const label=button.querySelector('span');if(label)label.textContent=dark?'Use light mode':'Use dark mode';button.setAttribute('aria-label',dark?'Use light mode':'Use dark mode')})};
    buttons.forEach(button=>button.addEventListener('click',()=>{const next=document.documentElement.dataset.theme==='dark'?'light':'dark';document.documentElement.dataset.theme=next;localStorage.setItem('bama-theme',next);render()}));render();
});
</script>
@auth
<script>
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const drawerOpenButton = document.getElementById('mobile-shell-open');
    const drawerCloseButton = document.getElementById('mobile-shell-close');
    const drawerBackdrop = document.getElementById('mobile-shell-backdrop');
    const drawer = document.getElementById('mobile-shell-drawer');
    const openButton = document.getElementById('mobile-overflow-open');
    const backdrop = document.getElementById('mobile-overflow-backdrop');
    const sheet = document.getElementById('mobile-overflow-menu');

    if (drawerOpenButton && drawerBackdrop && drawer) {
        const openDrawer = () => {
            body.classList.add('mobile-nav-open');
            drawerOpenButton.setAttribute('aria-expanded', 'true');
            drawer.setAttribute('aria-hidden', 'false');
            requestAnimationFrame(() => drawer.focus({ preventScroll:true }));
        };
        const closeDrawer = () => {
            body.classList.remove('mobile-nav-open');
            drawerOpenButton.setAttribute('aria-expanded', 'false');
            drawer.setAttribute('aria-hidden', 'true');
            drawerOpenButton.focus({ preventScroll:true });
        };

        drawerOpenButton.addEventListener('click', openDrawer);
        drawerCloseButton?.addEventListener('click', closeDrawer);
        drawerBackdrop.addEventListener('click', closeDrawer);
        drawer.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', closeDrawer));
        drawer.querySelectorAll('button[type="submit"]').forEach((button) => button.addEventListener('click', closeDrawer));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && body.classList.contains('mobile-nav-open')) closeDrawer();
        });
    }

    if (!openButton || !backdrop || !sheet) return;

    const openSheet = () => {
        body.classList.add('mobile-overflow-open');
        openButton.setAttribute('aria-expanded', 'true');
        sheet.setAttribute('aria-hidden', 'false');
    };
    const closeSheet = () => {
        body.classList.remove('mobile-overflow-open');
        openButton.setAttribute('aria-expanded', 'false');
        sheet.setAttribute('aria-hidden', 'true');
    };
    const toggleSheet = () => {
        if (body.classList.contains('mobile-overflow-open')) {
            closeSheet();
            return;
        }

        openSheet();
    };

    openButton.addEventListener('click', toggleSheet);
    backdrop.addEventListener('click', closeSheet);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSheet();
    });

    let startY = 0;
    let currentY = 0;
    sheet.addEventListener('touchstart', (event) => {
        startY = event.touches[0].clientY;
        currentY = startY;
    }, { passive:true });
    sheet.addEventListener('touchmove', (event) => {
        currentY = event.touches[0].clientY;
    }, { passive:true });
    sheet.addEventListener('touchend', () => {
        if (currentY - startY > 55) closeSheet();
    });
    sheet.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href || href === '#' || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                closeSheet();
                return;
            }

            event.preventDefault();
            closeSheet();
            window.location.assign(href);
        });
    });
    sheet.querySelectorAll('button[type="submit"]').forEach((item) => {
        item.addEventListener('click', closeSheet);
    });
});
</script>
@endauth
@vite('resources/js/app.js')
@stack('scripts')
</body>
</html>
