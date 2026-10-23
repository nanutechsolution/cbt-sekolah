@php
    $pengaturan = app(\App\Services\PengaturanSekolahService::class)->get();
    $selesai = $peserta->filter(fn ($p) => $p->status === \App\Enums\StatusPesertaUjian::Selesai)->count();
    $mengerjakan = $peserta->filter(fn ($p) => $p->status === \App\Enums\StatusPesertaUjian::Mengerjakan)->count();
    $ditangguhkan = $peserta->filter(fn ($p) => $p->status === \App\Enums\StatusPesertaUjian::Ditangguhkan)->count();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara</title>
    <style>
        @page { margin: 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; line-height: 1.5; }
        h1 { text-align:center; font-size:16px; margin:0; color:#0f766e; }
        .sub { text-align:center; color:#475569; margin:2px 0 18px; }
        table { width:100%; border-collapse:collapse; margin:8px 0 14px; }
        th, td { border:1px solid #94a3b8; padding:5px 6px; font-size:9.5px; }
        th { background:#f0fdfa; text-align:center; }
        .meta td { border:0; padding:2px 0; font-size:10.5px; }
        .summary { margin:10px 0; }
        .summary span { display:inline-block; margin-right:16px; }
        .signature { margin-top:24px; width:100%; }
        .signature td { border:0; width:50%; text-align:center; padding-top:50px; font-size:10px; }
        .line { border-top:1px solid #334155; width:72%; margin:0 auto; padding-top:5px; }
    </style>
</head>
<body>
    <h1>BERITA ACARA PELAKSANAAN UJIAN</h1>
    <div class="sub">{{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }}</div>

    <table class="meta">
        <tr><td style="width:150px;"><strong>Nama Ujian</strong></td><td>: {{ $jadwal->paketSoal?->nama ?: $jadwal->nama }}</td></tr>
        <tr><td><strong>Mata Pelajaran</strong></td><td>: {{ $jadwal->paketSoal?->mapel?->nama ?: '-' }}</td></tr>
        <tr><td><strong>Jenis Ujian</strong></td><td>: {{ $jadwal->paketSoal?->jenisUjian?->nama ?: '-' }}</td></tr>
        <tr><td><strong>Hari / Tanggal</strong></td><td>: {{ $jadwal->mulai->translatedFormat('l, d F Y') }}</td></tr>
        <tr><td><strong>Waktu</strong></td><td>: {{ $jadwal->mulai->format('H:i') }} — {{ $jadwal->selesai->format('H:i') }} ({{ $jadwal->durasi_menit }} menit)</td></tr>
        <tr><td><strong>Kelas</strong></td><td>: {{ $jadwal->kelas->pluck('nama')->implode(', ') }}</td></tr>
    </table>

    <div class="summary">
        <span>Jumlah siswa: <strong>{{ $siswa->count() }}</strong></span>
        <span>Selesai: <strong>{{ $selesai }}</strong></span>
        <span>Mengerjakan: <strong>{{ $mengerjakan }}</strong></span>
        <span>Ditangguhkan: <strong>{{ $ditangguhkan }}</strong></span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:25px;">No</th>
                <th>Nama Siswa</th>
                <th style="width:65px;">Kelas</th>
                <th style="width:80px;">Status</th>
                <th style="width:55px;">Nilai</th>
                <th style="width:65px;">Pelanggaran</th>
                <th style="width:95px;">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($siswa as $index => $s)
                @php $p = $peserta->get($s->id); @endphp
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $s->nama }}</td>
                    <td style="text-align:center;">{{ $s->kelas?->nama ?: '-' }}</td>
                    <td>{{ $p?->status?->getLabel() ?: 'Belum Mengerjakan' }}</td>
                    <td style="text-align:center;">{{ $p?->nilai !== null ? number_format((float) $p->nilai, 2) : '-' }}</td>
                    <td style="text-align:center;">{{ $p?->jumlah_pelanggaran ?? 0 }}</td>
                    <td style="text-align:center;">{{ $p?->waktu_selesai?->format('H:i') ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">Belum ada siswa pada jadwal ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td><div>Pengawas</div><div class="line">Nama dan Tanda Tangan</div></td>
            <td><div>{{ $pengaturan->kota ?: '' }}, {{ now()->format('d/m/Y') }}</div><div class="line">Kepala Sekolah</div></td>
        </tr>
    </table>
</body>
</html>
