@extends('layouts.siswa')

@section('title', 'Konfirmasi Ujian')

@section('content')
    <div style="max-width: 860px; margin: 0 auto;">
        <div style="margin-bottom: 20px;">
            <a href="{{ route('siswa.dashboard') }}" class="small" style="text-decoration:none;">&larr; Kembali ke dashboard</a>
            <h1 class="page-title" style="margin-top:10px;">Konfirmasi Identitas</h1>
            <p class="page-subtitle">Periksa data Anda sebelum memulai ujian.</p>
        </div>

        @if ($errors->any())
            <div class="alert danger">{{ $errors->first() }}</div>
        @endif

        @if ($peserta?->status === \App\Enums\StatusPesertaUjian::Ditangguhkan)
            <div class="alert danger">Ujian Anda ditangguhkan. Hubungi pengawas untuk informasi lebih lanjut.</div>
        @endif

        <div class="card" style="margin-bottom: 18px;">
            <div class="card-body">
                <div class="section-head">
                    <div>
                        <h2>{{ $jadwal->paketSoal?->nama ?: $jadwal->nama }}</h2>
                        <p>{{ $jadwal->paketSoal?->mapel?->nama ?: '-' }} · {{ $jadwal->paketSoal?->jenisUjian?->nama ?: 'Ujian' }}</p>
                    </div>
                    <span class="badge info">{{ $jadwal->durasi_menit }} menit</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;">
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Nama</div>
                        <strong>{{ $siswa->nama }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">NIS</div>
                        <strong>{{ $siswa->nis ?: '-' }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Kelas</div>
                        <strong>{{ $siswa->kelas?->nama ?: '-' }}</strong>
                    </div>
                    <div style="padding:12px; border:1px solid var(--line); border-radius:10px;">
                        <div class="muted small">Waktu</div>
                        <strong>{{ $jadwal->mulai->format('d/m/Y H:i') }} — {{ $jadwal->selesai->format('H:i') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 18px;">
            <div class="card-body">
                <div class="section-head">
                    <div>
                        <h2>Tata Tertib</h2>
                        <p>Dengan memulai ujian, Anda menyetujui aturan berikut.</p>
                    </div>
                </div>
                <ol class="rules">
                    <li>Kerjakan soal secara mandiri dan jangan membuka tab atau aplikasi lain.</li>
                    <li>Jawaban disimpan otomatis. Jangan menutup browser sebelum menekan tombol selesai.</li>
                    <li>Waktu berjalan sesuai jam server. Jawaban setelah waktu habis tidak akan diterima.</li>
                    <li>Perpindahan tab, klik kanan, dan copy/paste dicatat sebagai pelanggaran.</li>
                    <li>Ujian hanya dapat dilanjutkan pada perangkat yang sama. Hubungi pengawas jika perangkat bermasalah.</li>
                </ol>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if ($peserta?->status === \App\Enums\StatusPesertaUjian::Selesai)
                    <a class="btn block" href="{{ route('siswa.ujian.hasil', $peserta) }}">Lihat Hasil Ujian</a>
                @elseif (! $jadwal->bisaDimulai())
                    <div class="alert warning" style="margin-bottom: 12px;">
                        @if (now()->lt($jadwal->mulai))
                            Ujian belum dimulai. Silakan kembali pada waktu yang ditentukan.
                        @else
                            Waktu ujian telah berakhir.
                        @endif
                    </div>
                    <button class="btn block" disabled>Ujian Belum Tersedia</button>
                @else
                    <form method="POST" action="{{ route('siswa.ujian.mulai', $jadwal) }}">
                        @csrf
                        <button type="submit" class="btn block">
                            {{ $peserta?->status === \App\Enums\StatusPesertaUjian::Mengerjakan ? 'Lanjutkan Ujian' : 'Mulai Ujian Sekarang' }}
                        </button>
                    </form>
                    @if ($peserta?->status === \App\Enums\StatusPesertaUjian::Mengerjakan)
                        <p class="muted small" style="margin:12px 0 0; text-align:center;">
                            Jika tombol tidak berfungsi, ujian mungkin sudah dimulai di perangkat lain.
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
@endsection
