<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired | Bama</title>
    <style>
        :root {
            color-scheme: light;
            --green: #00A651;
            --green-dark: #007A3B;
            --lime: #dfff45;
            --ink: #071B12;
            --muted: #cfd7c7;
            --page: #050806;
            --panel: rgba(13, 18, 11, .92);
            --line: rgba(223, 255, 69, .12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 50% 9%, rgba(223, 255, 69, .14), transparent 25rem),
                linear-gradient(180deg, #0a1008, var(--page) 72%);
            color: #f7f9f2;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        main {
            width: min(640px, 100%);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            padding: clamp(28px, 7vw, 54px);
            box-shadow: 0 28px 80px rgba(0, 0, 0, .34);
        }

        .code {
            color: var(--lime);
            font-size: .82rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        h1 {
            margin: 12px 0;
            font-size: clamp(2rem, 8vw, 4.5rem);
            line-height: .95;
            letter-spacing: 0;
        }

        p {
            margin: 0 0 26px;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        a {
            display: inline-flex;
            align-items: center;
            min-height: 44px;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 800;
            text-decoration: none;
        }

        .primary {
            background: linear-gradient(90deg, #c8f32f, #e8ff59);
            color: #071006;
        }

        .primary:hover,
        .primary:focus {
            background: #edff77;
        }

        .secondary {
            border: 1px solid var(--line);
            color: #f7f9f2;
        }
    </style>
</head>
<body>
    @php
        $user = auth()->user();
        $dashboardRoute = match ($user?->role) {
            'super_admin' => 'platform.dashboard',
            'client_portal' => 'portal.dashboard',
            default => 'dashboard',
        };
        $primaryUrl = $user ? route($dashboardRoute) : route('login');
        $primaryLabel = $user ? 'Go to dashboard' : 'Go to login';
    @endphp
    <main>
        <div class="code">419 error</div>
        <h1>{{ $user ? 'Still signed in.' : 'Session expired.' }}</h1>
        <p>{{ $user ? 'That form session expired, but your account is already authenticated. Continue to your dashboard from a fresh secure page.' : 'Your secure form session timed out or changed. Open the login page again and submit the form from the fresh page.' }}</p>
        <div class="actions">
            <a class="primary" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
            <a class="secondary" href="{{ url('/') }}">Back home</a>
        </div>
    </main>
</body>
</html>
