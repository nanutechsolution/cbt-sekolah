<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <style>
        :root {
            --brand: #0f766e;
            --brand-dark: #115e59;
            --brand-soft: #ccfbf1;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #ffffff;
            --bg: #f1f5f9;
            --radius: 20px;
            --shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            display: grid;
            place-items: center;
            padding: 24px;
            color: var(--ink);
            background:
                radial-gradient(circle at 15% 0%, var(--brand-soft), transparent 38%),
                var(--bg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.55;
        }
        .error-card {
            width: min(560px, 100%);
            padding: clamp(28px, 6vw, 48px);
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .error-brand {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .error-code {
            margin-top: 18px;
            color: var(--brand);
            font-size: clamp(56px, 12vw, 84px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.06em;
        }
        h1 {
            margin: 12px 0 10px;
            font-size: clamp(22px, 4vw, 30px);
            line-height: 1.2;
            letter-spacing: -.03em;
        }
        .error-message {
            max-width: 430px;
            margin: 0 auto;
            color: var(--muted);
            font-size: 15px;
        }
        .error-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
        }
        .error-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 12px 20px;
            color: #fff;
            background: var(--brand);
            border: 1px solid transparent;
            border-radius: 12px;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
        }
        .error-button:hover { background: var(--brand-dark); }
        .error-button.secondary {
            color: var(--ink);
            background: #fff;
            border-color: var(--line);
        }
        .error-button.secondary:hover { background: #f8fafc; }
        .error-button:focus-visible {
            outline: 3px solid rgba(15, 118, 110, .35);
            outline-offset: 3px;
        }
        @media (max-width: 480px) {
            body { padding: 14px; }
            .error-card {
                padding: 28px 20px;
                border-radius: 16px;
            }
            .error-actions { flex-direction: column; }
            .error-button { width: 100%; }
        }
    </style>
</head>
<body>
    <main class="error-card">
        <div class="error-brand">{{ config('app.name') }}</div>
        <div class="error-code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p class="error-message">@yield('message')</p>
        <div class="error-actions">@yield('actions')</div>
    </main>
</body>
</html>
