@php
    $pengaturan = app(\App\Services\PengaturanSekolahService::class)->get();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta</title>
    <style>
        @page { margin: 18mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; margin: 0; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; color: #0f766e; }
        .header p { margin: 2px 0 0; color: #475569; font-size: 10px; }
        .card { border: 1.5px solid #0f766e; border-radius: 8px; padding: 14px; margin-bottom: 16px; page-break-inside: avoid; }
        .card-title { text-align:center; font-weight:bold; font-size: 13px; letter-spacing: 1px; margin: 0 0 3px; }
        .card-sub { text-align:center; color:#475569; font-size: 9px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 3px 0; }
        .photo { width: 90px; height: 115px; border: 1px solid #cbd5e1; text-align:center; background:#f8fafc; color:#64748b; font-size:9px; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .label { color:#64748b; width: 95px; }
        .value { font-weight: bold; }
        .key { font-family: DejaVu Sans Mono, monospace; font-size: 13px; letter-spacing: 2px; border:1px dashed #94a3b8; padding:4px 7px; display:inline-block; }
        .note { margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 7px; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $pengaturan->nama_sekolah ?: 'CBT Sekolah' }}</h1>
        <p>Kartu Peserta Ujian · {{ $jadwal->paketSoal?->jenisUjian?->nama ?: 'Ujian' }} · {{ $jadwal->paketSoal?->tahunAjaran?->nama ?: '' }}</p>
    </div>

    @forelse ($siswa as $s)
        @php
            $p = $penempatan->get($s->id);
            $foto = null;
            if ($s->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($s->foto)) {
                $foto = \Illuminate\Support\Facades\Storage::disk('public')->path($s->foto);
            }
        @endphp
        <div class="card">
            <div class="card-title">KARTU PESERTA UJIAN</div>
            <div class="card-sub">{{ $jadwal->paketSoal?->nama ?: $jadwal->nama }}</div>
            <table>
                <tr>
                    <td style="width: 105px;">
                        <div class="photo">
                            @if ($foto)
                                <img src="{{ $foto }}" alt="Foto">
                            @else
                                <div style="padding-top: 45px;">FOTO</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <table>
                            <tr><td class="label">Nama</td><td class="value">{{ $s->nama }}</td></tr>
                            <tr><td class="label">NIS</td><td class="value">{{ $s->nis ?: '-' }}</td></tr>
                            <tr><td class="label">Kelas</td><td class="value">{{ $s->kelas?->nama ?: '-' }}</td></tr>
                            <tr><td class="label">No. Peserta</td><td class="value">{{ $p?->no_peserta ?: '-' }}</td></tr>
                            <tr><td class="label">Ruang / Sesi</td><td class="value">{{ $p?->ruang?->nama ?: '-' }} / {{ $p?->sesi?->nama ?: '-' }}</td></tr>
                            <tr><td class="label">Username</td><td class="value">{{ $s->username }}</td></tr>
                            <tr><td class="label">Security Key</td><td><span class="key">{{ $s->security_key }}</span></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div class="note">
                Ujian: {{ $jadwal->paketSoal?->mapel?->nama ?: '-' }} · {{ $jadwal->mulai->format('d/m/Y H:i') }} — {{ $jadwal->selesai->format('H:i') }}.
                Simpan kartu ini dan jangan bagikan security key kepada orang lain.
            </div>
        </div>
    @empty
        <p>Belum ada siswa pada jadwal ini.</p>
    @endforelse
</body>
</html>
