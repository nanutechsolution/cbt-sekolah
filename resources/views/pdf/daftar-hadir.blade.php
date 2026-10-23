@php
    $pengaturan = app(\App\Services\PengaturanSekolahService::class)->get();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Hadir</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; }
        .header { text-align:center; border-bottom:2px solid #0f766e; padding-bottom:8px; margin-bottom:14px; }
        .header h1 { margin:0; font-size:16px; color:#0f766e; }
        .header p { margin:3px 0 0; color:#475569; }
        .meta { width:100%; margin-bottom:12px; font-size:10px; }
        .meta td { padding:2px 0; }
        table.data { width:100%; border-collapse:collapse; }
        table.data th, table.data td { border:1px solid #94a3b8; padding:6px 7px; text-align:left; }
        table.data th { background:#f0fdfa; color:#115e59; font-size:10px; text-align:center; }
        table.data td { font-size:10px; }
        .center { text-align:center; }
        .signature { margin-top:28px; width:100%; }
        .signature td { width:50%; text-align:center; padding-top:45px; font-size:10px; }
        .signature .line { border-top:1px solid #334155; width:70%; margin:0 auto; padding-top:5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }}</h1>
        <p>DAFTAR HADIR PESERTA UJIAN</p>
    </div>

    <table class="meta">
        <tr><td><strong>Ujian</strong></td><td>: {{ $jadwal->paketSoal?->nama ?: $jadwal->nama }}</td></tr>
        <tr><td><strong>Mata Pelajaran</strong></td><td>: {{ $jadwal->paketSoal?->mapel?->nama ?: '-' }}</td></tr>
        <tr><td><strong>Tanggal / Waktu</strong></td><td>: {{ $jadwal->mulai->format('d/m/Y H:i') }} — {{ $jadwal->selesai->format('H:i') }}</td></tr>
        <tr><td><strong>Kelas</strong></td><td>: {{ $jadwal->kelas->pluck('nama')->implode(', ') }}</td></tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th style="width:85px;">No. Peserta</th>
                <th>Nama Siswa</th>
                <th style="width:70px;">NIS</th>
                <th style="width:70px;">Kelas</th>
                <th style="width:110px;">Ruang / Sesi</th>
                <th style="width:130px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($siswa as $index => $s)
                @php $p = $penempatan->get($s->id); @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $p?->no_peserta ?: '-' }}</td>
                    <td>{{ $s->nama }}</td>
                    <td class="center">{{ $s->nis ?: '-' }}</td>
                    <td class="center">{{ $s->kelas?->nama ?: '-' }}</td>
                    <td class="center">{{ $p?->ruang?->nama ?: '-' }} / {{ $p?->sesi?->nama ?: '-' }}</td>
                    <td></td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Belum ada siswa pada jadwal ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>
                <div>Pengawas</div>
                <div class="line">Nama dan Tanda Tangan</div>
            </td>
            <td>
                <div>{{ $pengaturan->kota ?: '' }}, {{ now()->format('d/m/Y') }}</div>
                <div class="line">Kepala Sekolah</div>
            </td>
        </tr>
    </table>
</body>
</html>
