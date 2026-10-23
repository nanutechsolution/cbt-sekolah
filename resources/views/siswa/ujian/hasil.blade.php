@extends('layouts.siswa')

@section('title', 'Hasil Ujian')

@section('content')
    @php
        $paket = $peserta->jadwalUjian?->paketSoal;
        $tampilkanNilai = (bool) $paket?->tampilkan_nilai;
    @endphp

    <div style="max-width: 820px; margin: 0 auto;">
        <div style="margin-bottom: 20px;">
            <a href="{{ route('siswa.dashboard') }}" class="small" style="text-decoration:none;">&larr; Kembali ke dashboard</a>
            <h1 class="page-title" style="margin-top:10px;">Hasil Ujian</h1>
            <p class="page-subtitle">{{ $paket?->nama ?: $peserta->jadwalUjian?->nama }}</p>
        </div>

        @if ($tampilkanNilai)
            <div class="card" style="margin-bottom: 18px;">
                <div class="card-body" style="text-align:center;">
                    <div class="muted small" style="text-transform:uppercase; letter-spacing:.1em; font-weight:800;">Nilai Akhir</div>
                    <div class="score-big">{{ $peserta->nilai !== null ? number_format((float) $peserta->nilai, 2) : '-' }}</div>
                    <div class="muted small">Nilai PG: {{ $peserta->nilai_pg !== null ? number_format((float) $peserta->nilai_pg, 2) : '-' }}</div>
                    @if ($paket?->soal()->where('tipe', \App\Enums\TipeSoal::Essay)->exists())
                        <p class="muted small" style="margin: 12px 0 0;">Nilai essay akan dilengkapi oleh guru.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="alert info">Nilai ujian ini tidak ditampilkan oleh sekolah.</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="section-head">
                    <div>
                        <h2>Ringkasan</h2>
                        <p>Simpan halaman ini sebagai bukti Anda telah menyelesaikan ujian.</p>
                    </div>
                    <span class="badge success">Selesai</span>
                </div>
                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;">
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Nama</div>
                        <strong>{{ $siswa->nama }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Kelas</div>
                        <strong>{{ $siswa->kelas?->nama ?: '-' }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Mulai</div>
                        <strong>{{ $peserta->waktu_mulai?->format('d/m/Y H:i') ?: '-' }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Selesai</div>
                        <strong>{{ $peserta->waktu_selesai?->format('d/m/Y H:i') ?: '-' }}</strong>
                    </div>
                </div>
                <p class="muted small" style="margin:16px 0 0;">
                    Jumlah pelanggaran tercatat: <strong>{{ $peserta->jumlah_pelanggaran }}</strong>
                </p>
            </div>
        </div>
    </div>
@endsection
