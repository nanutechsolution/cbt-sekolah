@php
    $pengaturan = app(\App\Services\PengaturanSekolahService::class)->get();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Ujian') — {{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }}</title>
    @livewireStyles
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
            --danger: #b91c1c;
            --danger-soft: #fee2e2;
            --warning: #b45309;
            --warning-soft: #fef3c7;
            --success: #15803d;
            --success-soft: #dcfce7;
            --radius: 16px;
            --shadow: 0 12px 32px rgba(15, 23, 42, .08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--ink);
            line-height: 1.55;
        }
        a { color: var(--brand-dark); }
        .student-shell { min-height: 100vh; display: flex; flex-direction: column; }
        .student-header {
            background: var(--surface);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .student-header-inner {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--ink); }
        .brand-mark {
            width: 40px; height: 40px; border-radius: 12px;
            display: grid; place-items: center;
            background: var(--brand); color: #fff; font-weight: 800;
        }
        .brand-title { font-weight: 800; font-size: 15px; line-height: 1.2; }
        .brand-subtitle { color: var(--muted); font-size: 12px; }
        .header-user { display: flex; align-items: center; gap: 14px; font-size: 13px; color: var(--muted); }
        .header-user strong { color: var(--ink); }
        .student-main { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 32px 0 56px; flex: 1; }
        .footer { color: var(--muted); text-align: center; font-size: 12px; padding: 20px; }
        .page-title { font-size: clamp(24px, 3vw, 34px); line-height: 1.2; margin: 0 0 6px; letter-spacing: -.03em; }
        .page-subtitle { color: var(--muted); margin: 0 0 24px; }
        .card {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--radius); box-shadow: var(--shadow);
        }
        .card-body { padding: 24px; }
        .hero {
            background: linear-gradient(135deg, #134e4a, #0f766e 55%, #0d9488);
            color: #fff; border: 0;
        }
        .hero .muted-light { color: rgba(255,255,255,.72); }
        .hero h1 { margin: 0 0 8px; font-size: clamp(24px, 3vw, 34px); letter-spacing: -.03em; }
        .hero-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 24px; align-items: end; }
        .identity-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .identity-item { background: rgba(255,255,255,.1); padding: 12px 14px; border-radius: 12px; }
        .identity-item span { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; color: rgba(255,255,255,.65); }
        .identity-item strong { font-size: 14px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border: 1px solid transparent; border-radius: 10px; padding: 10px 16px;
            font: inherit; font-weight: 700; font-size: 14px; text-decoration: none;
            cursor: pointer; transition: .15s ease; background: var(--brand); color: #fff;
        }
        .btn:hover { background: var(--brand-dark); }
        .btn:disabled { opacity: .55; cursor: not-allowed; }
        .btn.secondary { background: #fff; border-color: var(--line); color: var(--ink); }
        .btn.secondary:hover { background: #f8fafc; }
        .btn.danger { background: var(--danger); }
        .btn.warn { background: var(--warning); }
        .btn.small { padding: 7px 12px; font-size: 12px; }
        .btn.block { width: 100%; }
        .badge {
            display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px;
            border-radius: 999px; font-size: 11px; font-weight: 800; line-height: 1.4;
            background: #f1f5f9; color: #334155; white-space: nowrap;
        }
        .badge.success { background: var(--success-soft); color: var(--success); }
        .badge.warning { background: var(--warning-soft); color: var(--warning); }
        .badge.danger { background: var(--danger-soft); color: var(--danger); }
        .badge.info { background: var(--brand-soft); color: var(--brand-dark); }
        .alert { padding: 13px 15px; border-radius: 12px; border: 1px solid transparent; font-size: 14px; margin-bottom: 16px; }
        .alert.danger { background: var(--danger-soft); color: #7f1d1d; border-color: #fecaca; }
        .alert.warning { background: var(--warning-soft); color: #78350f; border-color: #fde68a; }
        .alert.success { background: var(--success-soft); color: #14532d; border-color: #bbf7d0; }
        .alert.info { background: #e0f2fe; color: #075985; border-color: #bae6fd; }
        .section-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 14px; }
        .section-head h2 { margin: 0; font-size: 18px; letter-spacing: -.02em; }
        .section-head p { margin: 2px 0 0; color: var(--muted); font-size: 13px; }
        .exam-list { display: grid; gap: 14px; }
        .exam-card { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: center; padding: 20px; }
        .exam-card h3 { margin: 0 0 4px; font-size: 17px; }
        .exam-meta { display: flex; flex-wrap: wrap; gap: 8px 16px; color: var(--muted); font-size: 13px; margin-top: 8px; }
        .exam-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
        .empty { padding: 36px 20px; text-align: center; color: var(--muted); }
        .empty strong { display: block; color: var(--ink); margin-bottom: 4px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        th { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; background: #f8fafc; }
        tr:last-child td { border-bottom: 0; }
        .form-grid { display: grid; gap: 16px; }
        .field label { display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; }
        .field input, .field textarea, .field select {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 11px 12px;
            font: inherit; font-size: 14px; background: #fff; color: var(--ink);
        }
        .field input:focus, .field textarea:focus, .field select:focus { outline: 3px solid rgba(15,118,110,.15); border-color: var(--brand); }
        .auth-shell { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: radial-gradient(circle at 20% 0%, #ccfbf1, transparent 40%), var(--bg); }
        .auth-card { width: min(440px, 100%); }
        .auth-head { text-align: center; margin-bottom: 22px; }
        .auth-head .brand-mark { margin: 0 auto 12px; width: 52px; height: 52px; border-radius: 16px; font-size: 20px; }
        .auth-head h1 { margin: 0 0 4px; font-size: 24px; letter-spacing: -.03em; }
        .auth-head p { margin: 0; color: var(--muted); font-size: 13px; }
        .muted { color: var(--muted); }
        .small { font-size: 12px; }
        .rules { margin: 0; padding-left: 20px; color: #334155; font-size: 14px; }
        .rules li { margin: 7px 0; }
        .score-big { font-size: 48px; line-height: 1; font-weight: 900; color: var(--brand-dark); letter-spacing: -.04em; }
        @media (max-width: 760px) {
            .hero-grid, .exam-card { grid-template-columns: 1fr; }
            .exam-actions { align-items: stretch; }
            .header-user .user-name { display: none; }
            .student-main { padding-top: 20px; }
        }
        @yield('styles')
    </style>
</head>
<body>
    <div class="student-shell">
        <header class="student-header">
            <div class="student-header-inner">
                <a href="{{ Route::has('siswa.dashboard') ? route('siswa.dashboard') : url('/') }}" class="brand">
                    <div class="brand-mark">CBT</div>
                    <div>
                        <div class="brand-title">{{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }}</div>
                        <div class="brand-subtitle">Ruang ujian siswa</div>
                    </div>
                </a>
                @auth('siswa')
                    <div class="header-user">
                        <span class="user-name"><strong>{{ auth('siswa')->user()->nama }}</strong></span>
                        <form method="POST" action="{{ route('siswa.logout') }}">
                            @csrf
                            <button type="submit" class="btn secondary small">Keluar</button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        <main class="student-main">
            @yield('content')
        </main>

        <footer class="footer">
            {{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }} · Ujian berbasis komputer
        </footer>
    </div>
    @livewireScripts
</body>
</html>
