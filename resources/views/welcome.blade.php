<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $namaSekolah }}</title>
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
        .home-card {
            width: min(640px, 100%);
            padding: clamp(28px, 6vw, 52px);
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .school-mark {
            display: grid;
            place-items: center;
            width: 84px;
            height: 84px;
            margin: 0 auto 20px;
            overflow: hidden;
            color: #fff;
            background: var(--brand);
            border-radius: 24px;
            font-size: 30px;
            font-weight: 800;
        }
        .school-mark img {
            width: 100%;
            height: 100%;
            padding: 8px;
            object-fit: contain;
            background: #fff;
        }
        .school-name {
            margin: 0;
            overflow-wrap: anywhere;
            font-size: clamp(24px, 4vw, 34px);
            line-height: 1.2;
            letter-spacing: -.03em;
        }
        .school-subtitle {
            margin: 10px 0 28px;
            color: var(--muted);
            font-size: 14px;
        }
        .btn-masuk {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 60px;
            padding: 16px 24px;
            color: #fff;
            background: var(--brand);
            border-radius: 14px;
            font-size: clamp(17px, 2.4vw, 20px);
            font-weight: 800;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }
        .btn-masuk:hover { background: var(--brand-dark); }
        .btn-masuk:active { transform: translateY(1px); }
        .btn-masuk:focus-visible {
            outline: 3px solid rgba(15, 118, 110, .35);
            outline-offset: 3px;
        }
        .petunjuk {
            margin: 18px 0 0;
            color: var(--muted);
            font-size: 14px;
        }
        .admin-links {
            margin: 28px 0 0;
            padding-top: 20px;
            color: var(--muted);
            border-top: 1px solid var(--line);
            font-size: 13px;
        }
        .admin-links a {
            color: var(--brand-dark);
            font-weight: 700;
            text-decoration: none;
        }
        .admin-links a:hover { text-decoration: underline; }
        .admin-links a:focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 3px;
            border-radius: 3px;
        }
        @media (max-width: 480px) {
            body { padding: 14px; }
            .home-card {
                padding: 28px 20px;
                border-radius: 16px;
            }
            .school-mark {
                width: 72px;
                height: 72px;
                border-radius: 20px;
                font-size: 26px;
            }
            .school-subtitle { margin-bottom: 22px; }
        }
    </style>
</head>
<body>
    <main class="home-card">
        <div class="school-mark" aria-hidden="true">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $namaSekolah }}">
            @else
                <span class="school-mark-initial">{{ $inisial }}</span>
            @endif
        </div>

        <h1 class="school-name">{{ $namaSekolah }}</h1>
        <p class="school-subtitle">Ujian berbasis komputer</p>

        <a class="btn-masuk" href="{{ url('/ujian') }}">Masuk Ujian</a>

        <p class="petunjuk">Gunakan username dan security key dari kartu peserta.</p>

        <nav class="admin-links" aria-label="Panel admin dan guru">
            <a href="{{ url('/admin') }}">Admin</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ url('/guru') }}">Guru</a>
        </nav>
    </main>
</body>
</html>
