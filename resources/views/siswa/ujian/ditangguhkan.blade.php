@extends('layouts.siswa')

@section('title', 'Ujian Ditangguhkan')

@section('content')
    <div style="max-width: 640px; margin: 0 auto;">
        <div class="card">
            <div class="card-body" style="text-align:center;">
                <div style="font-size: 52px; line-height: 1; margin-bottom: 12px;">⏸</div>
                <h1 style="margin:0 0 8px;">Ujian Ditangguhkan</h1>
                <p class="muted" style="margin:0 0 20px;">
                    Ujian Anda dihentikan sementara karena melebihi batas pelanggaran.
                    Silakan hubungi pengawas atau guru untuk melanjutkan.
                </p>
                <div class="alert warning" style="text-align:left;">
                    <strong>{{ $peserta->jadwalUjian?->paketSoal?->nama ?: $peserta->jadwalUjian?->nama }}</strong><br>
                    Pelanggaran tercatat: {{ $peserta->jumlah_pelanggaran }}
                </div>
                <a class="btn secondary block" href="{{ route('siswa.dashboard') }}">Kembali ke Dashboard</a>
            </div>
        </div>
    </div>
@endsection
