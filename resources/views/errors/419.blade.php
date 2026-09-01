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
            --ink: #071B12;
            --muted: #667085;
            --page: #F7F8F5;
            --line: #D8E5DC;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--page);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            letter-spacing: 0;
        }

        main {
            width: min(640px, 100%);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            padding: clamp(28px, 7vw, 54px);
            box-shadow: 0 18px 45px rgba(7, 27, 18, .08);
        }

        .code {
            color: var(--green-dark);
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
            background: var(--green);
            color: #fff;
        }

        .primary:hover,
        .primary:focus {
            background: var(--green-dark);
        }

        .secondary {
            border: 1px solid var(--line);
            color: var(--ink);
        }
    </style>
</head>
<body>
    <main>
        <div class="code">419 error</div>
        <h1>Session expired.</h1>
        <p>Your secure form session timed out or changed. Open the login page again and submit the form from the fresh page.</p>
        <div class="actions">
            <a class="primary" href="{{ route('login') }}">Go to login</a>
            <a class="secondary" href="{{ url('/') }}">Back home</a>
        </div>
    </main>
</body>
</html>
