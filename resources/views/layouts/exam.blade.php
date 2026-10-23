<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Ujian') — CBT Sekolah</title>
    @livewireStyles
    <style>
        :root {
            --brand: #0f766e;
            --brand-dark: #115e59;
            --brand-soft: #ccfbf1;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --surface: #fff;
            --bg: #f1f5f9;
            --danger: #b91c1c;
            --danger-soft: #fee2e2;
            --warning: #b45309;
            --warning-soft: #fef3c7;
            --success: #15803d;
            --success-soft: #dcfce7;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--ink); line-height: 1.5; }
        button, input, textarea { font: inherit; }
        .exam-shell { min-height: 100vh; display: flex; flex-direction: column; user-select: none; }
        .exam-top {
            background: #0f172a; color: #fff; padding: 12px 18px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            position: sticky; top: 0; z-index: 20;
        }
        .exam-brand { display:flex; align-items:center; gap:12px; min-width:0; }
        .exam-mark { width: 38px; height: 38px; border-radius: 10px; background: var(--brand); display:grid; place-items:center; font-weight:900; font-size:12px; flex:none; }
        .exam-title { font-weight: 800; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .exam-sub { color: #94a3b8; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .exam-clock { display:flex; align-items:center; gap:14px; }
        .clock-box { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14); border-radius: 10px; padding: 7px 12px; text-align:center; }
        .clock-label { color: #94a3b8; font-size: 9px; text-transform: uppercase; letter-spacing: .12em; font-weight: 800; }
        .clock-value { font-variant-numeric: tabular-nums; font-weight: 900; font-size: 22px; line-height: 1.1; letter-spacing: .03em; }
        .clock-value.low { color: #fca5a5; }
        .exam-user { text-align:right; font-size:12px; color:#cbd5e1; }
        .exam-user strong { color:#fff; }
        .exam-body { display:grid; grid-template-columns: 290px minmax(0, 1fr); gap: 18px; width:min(1240px, calc(100% - 28px)); margin: 18px auto 40px; align-items:start; }
        .exam-nav, .exam-main { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; box-shadow: 0 8px 24px rgba(15,23,42,.06); }
        .exam-nav { padding: 16px; position: sticky; top: 86px; }
        .exam-nav h2 { margin: 0 0 4px; font-size: 14px; }
        .exam-nav p { margin: 0 0 12px; color: var(--muted); font-size: 11px; }
        .nomor-grid { display:grid; grid-template-columns: repeat(5, 1fr); gap: 7px; }
        .nomor-btn { border:1px solid var(--line); background:#fff; border-radius:8px; padding:8px 0; font-size:12px; font-weight:800; cursor:pointer; color:#334155; position:relative; }
        .nomor-btn:hover { border-color: var(--brand); }
        .nomor-btn.active { background: var(--brand); border-color: var(--brand); color:#fff; }
        .nomor-btn.answered { background: var(--brand-soft); border-color: #99f6e4; color: #115e59; }
        .nomor-btn.active.answered { background: var(--brand); color:#fff; }
        .nomor-btn.ragu::after { content:""; position:absolute; top:4px; right:4px; width:6px; height:6px; border-radius:50%; background:#f59e0b; }
        .legend { display:flex; flex-direction:column; gap:6px; margin-top:16px; color:var(--muted); font-size:11px; }
        .legend span { display:flex; align-items:center; gap:7px; }
        .dot { width:10px; height:10px; border-radius:3px; border:1px solid var(--line); display:inline-block; }
        .dot.answered { background: var(--brand-soft); border-color:#99f6e4; }
        .dot.ragu { background:#f59e0b; border-color:#f59e0b; }
        .exam-main { min-height: 520px; padding: 24px; }
        .exam-meta { display:flex; justify-content:space-between; gap:12px; align-items:center; padding-bottom:14px; border-bottom:1px solid var(--line); margin-bottom:18px; }
        .exam-meta h1 { margin:0; font-size:18px; letter-spacing:-.02em; }
        .exam-meta span { color:var(--muted); font-size:12px; }
        .exam-meta-right { display:flex; align-items:center; gap:9px; flex-wrap:wrap; justify-content:flex-end; }
        .font-tools { display:flex; align-items:center; gap:3px; border:1px solid var(--line); border-radius:8px; padding:2px; background:#f8fafc; }
        .font-button { border:0; border-radius:6px; background:transparent; color:var(--muted); font-size:11px; font-weight:800; padding:4px 6px; cursor:pointer; }
        .font-button:hover, .font-button.active { background:var(--brand-soft); color:var(--brand-dark); }
        .question-text { font-size:calc(16px * var(--exam-font-scale, 1)); line-height:1.7; margin-bottom:16px; }
        .question-text p { margin:0 0 10px; }
        .question-image { display:block; max-width:100%; max-height:340px; border-radius:10px; margin:12px 0; border:1px solid var(--line); }
        .audio-box { border:1px solid #bae6fd; background:#f0f9ff; border-radius:10px; padding:10px 12px; margin:0 0 16px; }
        .audio-heading { display:flex; justify-content:space-between; gap:10px; align-items:center; font-size:11px; color:#075985; margin-bottom:7px; flex-wrap:wrap; }
        .audio-heading strong { font-size:12px; }
        .question-audio { width:100%; height:38px; display:block; }
        .answer-hint { margin:0 0 9px; color:var(--muted); font-size:12px; }
        .field-label { display:block; margin:0 0 7px; font-size:12px; font-weight:800; color:#334155; }
        .text-answer { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:11px 12px; font-size:calc(14px * var(--exam-font-scale, 1)); user-select:text; }
        .text-answer:focus { outline:3px solid rgba(15,118,110,.14); border-color:var(--brand); }
        .options { display:grid; gap:9px; }
        .option { display:flex; gap:11px; align-items:flex-start; border:1px solid var(--line); border-radius:10px; padding:12px 13px; cursor:pointer; transition:.12s ease; background:#fff; }
        .option:hover { border-color:#94a3b8; background:#f8fafc; }
        .option.selected { border-color:var(--brand); background:#f0fdfa; box-shadow:0 0 0 2px rgba(15,118,110,.1); }
        .option input { margin-top:4px; accent-color:var(--brand); flex:none; }
        .option-label { font-weight:900; color:var(--brand-dark); width:20px; flex:none; }
        .option-text { flex:1; font-size:calc(14px * var(--exam-font-scale, 1)); }
        .option-image { display:block; max-width:220px; max-height:140px; border-radius:6px; margin-top:7px; }
        .match-list, .statement-list, .order-list { display:grid; gap:8px; }
        .match-row { display:grid; grid-template-columns:minmax(0,1fr) minmax(170px, .7fr); gap:12px; align-items:center; border:1px solid var(--line); border-radius:10px; padding:11px 12px; }
        .match-left { display:flex; gap:8px; align-items:flex-start; font-size:calc(14px * var(--exam-font-scale, 1)); }
        .match-select { width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px; background:#fff; font-size:13px; }
        .statement-row { display:flex; justify-content:space-between; gap:12px; align-items:center; border:1px solid var(--line); border-radius:10px; padding:11px 12px; flex-wrap:wrap; }
        .statement-text { display:flex; gap:8px; align-items:flex-start; flex:1; min-width:200px; font-size:calc(14px * var(--exam-font-scale, 1)); }
        .statement-choices { display:flex; gap:7px; flex:none; }
        .statement-choices label { display:inline-flex; align-items:center; gap:5px; border:1px solid var(--line); border-radius:8px; padding:6px 9px; font-size:12px; font-weight:700; cursor:pointer; }
        .statement-choices label.selected { border-color:var(--brand); background:var(--brand-soft); color:var(--brand-dark); }
        .statement-choices input { accent-color:var(--brand); }
        .order-row { display:flex; align-items:center; gap:10px; border:1px solid var(--line); border-radius:10px; padding:10px 12px; }
        .order-number { width:26px; height:26px; border-radius:999px; display:grid; place-items:center; background:var(--brand-soft); color:var(--brand-dark); font-size:12px; font-weight:900; flex:none; }
        .order-text { flex:1; font-size:calc(14px * var(--exam-font-scale, 1)); }
        .order-buttons { display:flex; gap:4px; flex:none; }
        textarea { width:100%; min-height:190px; resize:vertical; border:1px solid #cbd5e1; border-radius:10px; padding:12px; font-size:calc(14px * var(--exam-font-scale, 1)); line-height:1.6; user-select:text; }
        textarea:focus { outline:3px solid rgba(15,118,110,.14); border-color:var(--brand); }
        .exam-actions { display:flex; justify-content:space-between; gap:10px; align-items:center; margin-top:22px; padding-top:16px; border-top:1px solid var(--line); flex-wrap:wrap; }
        .exam-actions-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid transparent; border-radius:9px; padding:9px 14px; font-size:13px; font-weight:800; cursor:pointer; background:var(--brand); color:#fff; text-decoration:none; }
        .btn:hover { background:var(--brand-dark); }
        .btn.secondary { background:#fff; border-color:var(--line); color:var(--ink); }
        .btn.secondary:hover { background:#f8fafc; }
        .btn.warn { background:var(--warning); }
        .btn.danger { background:var(--danger); }
        .btn.small { padding:4px 8px; font-size:12px; line-height:1; }
        .btn:disabled { opacity:.5; cursor:not-allowed; }
        .badge { display:inline-flex; align-items:center; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:900; background:#f1f5f9; color:#334155; white-space:nowrap; }
        .badge.warning { background:var(--warning-soft); color:var(--warning); }
        .badge.danger { background:var(--danger-soft); color:var(--danger); }
        .badge.success { background:var(--success-soft); color:var(--success); }
        .alert { border-radius:10px; padding:11px 13px; font-size:13px; margin-bottom:13px; border:1px solid transparent; }
        .alert.danger { background:var(--danger-soft); border-color:#fecaca; color:#7f1d1d; }
        .alert.warning { background:var(--warning-soft); border-color:#fde68a; color:#78350f; }
        .alert.info { background:#e0f2fe; border-color:#bae6fd; color:#075985; }
        .save-state { color:var(--muted); font-size:11px; min-height:16px; }
        .mobile-nav { display:none; }
        @media (max-width: 920px) {
            .exam-body { grid-template-columns: 1fr; width: calc(100% - 20px); margin-top: 10px; }
            .exam-nav { position: static; order: 2; }
            .exam-main { order: 1; padding: 18px; }
            .exam-user { display:none; }
            .exam-title { max-width: 46vw; }
            .exam-sub { max-width: 46vw; }
        }
        @media (max-width: 520px) {
            .exam-top { padding: 10px 12px; }
            .clock-value { font-size: 19px; }
            .nomor-grid { grid-template-columns: repeat(6, 1fr); }
            .exam-meta { align-items:flex-start; }
            .match-row { grid-template-columns: 1fr; }
            .statement-row { align-items:flex-start; }
            .statement-choices { width:100%; }
            .statement-choices label { flex:1; justify-content:center; }
        }
    </style>
    @yield('styles')
</head>
<body>
    @yield('content')
    @livewireScripts
    @stack('scripts')
</body>
</html>
