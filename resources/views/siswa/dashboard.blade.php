@extends('layouts.siswa')

@section('title', 'Dashboard Siswa')

@section('content')
    <section class="card hero" style="padding: 26px; margin-bottom: 24px;">
        <div class="hero-grid">
            <div>
                <div class="muted-light small" style="text-transform: uppercase; letter-spacing: .12em; font-weight: 800;">Selamat datang</div>
                <h1>{{ $siswa->nama }}</h1>
                <p class="muted-light" style="margin: 0;">Pilih ujian aktif di bawah ini. Pastikan perangkat dan koneksi Anda siap sebelum memulai.</p>
            </div>
            <div class="identity-grid">
                <div class="identity-item">
                    <span>NIS</span>
                    <strong>{{ $siswa->nis ?: '-' }}</strong>
                </div>
                <div class="identity-item">
                    <span>Kelas</span>
                    <strong>{{ $siswa->kelas?->nama ?: '-' }}</strong>
                </div>
                <div class="identity-item">
                    <span>Username</span>
                    <strong>{{ $siswa->username }}</strong>
                </div>
                <div class="identity-item">
                    <span>Status</span>
                    <strong>Aktif</strong>
                </div>
            </div>
        </div>
    </section>

    <div class="section-head">
        <div>
            <h2>Ujian Aktif</h2>
            <p>Jadwal yang dapat Anda ikuti hari ini.</p>
        </div>
    </div>

    @if ($jadwal->isEmpty())
        <div class="card empty">
            <strong>Belum ada ujian aktif.</strong>
            Ujian akan muncul di sini setelah pengawas mengaktifkan jadwal.
        </div>
    @else
        <div class="exam-list">
            @foreach ($jadwal as $item)
                @php
                    $p = $peserta->get($item->id);
                    $bisaDimulai = $item->bisaDimulai();
                    $statusPeserta = $p?->status;
                    $tokenSesi = $p ? session(\App\Services\PesertaUjianService::kunciSesi($p->id)) : null;
                    $tokenCocok = $p && is_string($tokenSesi) && hash_equals((string) $p->device_token, $tokenSesi);
                @endphp
                <div class="card exam-card">
                    <div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">
                            <span class="badge info">{{ $item->paketSoal?->jenisUjian?->nama ?: 'Ujian' }}</span>
                            @if ($statusPeserta === \App\Enums\StatusPesertaUjian::Selesai)
                                <span class="badge success">Selesai</span>
                            @elseif ($statusPeserta === \App\Enums\StatusPesertaUjian::Mengerjakan)
                                <span class="badge warning">Sedang dikerjakan</span>
                            @elseif ($statusPeserta === \App\Enums\StatusPesertaUjian::Ditangguhkan)
                                <span class="badge danger">Ditangguhkan</span>
                            @elseif ($bisaDimulai)
                                <span class="badge success">Siap dimulai</span>
                            @elseif (now()->lt($item->mulai))
                                <span class="badge">Belum dimulai</span>
                            @else
                                <span class="badge">Berakhir</span>
                            @endif
                        </div>
                        <h3>{{ $item->paketSoal?->nama ?: $item->nama }}</h3>
                        <div class="exam-meta">
                            <span>{{ $item->paketSoal?->mapel?->nama ?: '-' }}</span>
                            <span>{{ $item->mulai->format('d/m/Y H:i') }} — {{ $item->selesai->format('H:i') }}</span>
                            <span>{{ $item->durasi_menit }} menit</span>
                        </div>
                    </div>
                    <div class="exam-actions">
                        @if ($statusPeserta === \App\Enums\StatusPesertaUjian::Selesai)
                            <a class="btn secondary" href="{{ route('siswa.ujian.hasil', $p) }}">Lihat Hasil</a>
                        @elseif ($statusPeserta === \App\Enums\StatusPesertaUjian::Mengerjakan && $tokenCocok)
                            <a class="btn" href="{{ route('siswa.ujian.ruang', $p) }}">Lanjutkan Ujian</a>
                        @elseif ($statusPeserta === \App\Enums\StatusPesertaUjian::Mengerjakan)
                            <span class="small muted">Sesi di perangkat lain</span>
                        @elseif ($statusPeserta === \App\Enums\StatusPesertaUjian::Ditangguhkan)
                            <span class="small" style="color:var(--danger);">Hubungi pengawas</span>
                        @elseif ($bisaDimulai)
                            <a class="btn" href="{{ route('siswa.ujian.konfirmasi', $item) }}">Mulai Ujian</a>
                        @else
                            <span class="small muted">{{ now()->lt($item->mulai) ? 'Menunggu waktu mulai' : 'Waktu telah berakhir' }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="section-head" style="margin-top: 30px;">
        <div>
            <h2>Riwayat Ujian</h2>
            <p>Hasil ujian yang sudah diselesaikan.</p>
        </div>
    </div>

    <div class="card">
        @if ($riwayat->isEmpty())
            <div class="empty">Belum ada ujian yang diselesaikan.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ujian</th>
                            <th>Mata Pelajaran</th>
                            <th>Selesai</th>
                            <th>Nilai</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $row)
                            <tr>
                                <td><strong>{{ $row->jadwalUjian?->paketSoal?->nama ?: '-' }}</strong></td>
                                <td>{{ $row->jadwalUjian?->paketSoal?->mapel?->nama ?: '-' }}</td>
                                <td>{{ $row->waktu_selesai?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td>
                                    @if ($row->jadwalUjian?->paketSoal?->tampilkan_nilai)
                                        <strong>{{ $row->nilai !== null ? number_format((float) $row->nilai, 2) : '-' }}</strong>
                                    @else
                                        <span class="muted">Disembunyikan</span>
                                    @endif
                                </td>
                                <td><a class="btn secondary small" href="{{ route('siswa.ujian.hasil', $row) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
