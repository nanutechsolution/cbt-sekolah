<?php

namespace App\Http\Controllers;

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPesertaUjian;
use App\Models\JadwalUjian;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaDashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $jadwal = JadwalUjian::query()
            ->with(['paketSoal.mapel', 'paketSoal.jenisUjian'])
            ->where('status', StatusJadwalUjian::Aktif->value)
            ->where('selesai', '>=', now()->subDay())
            ->whereHas('kelas', fn ($query) => $query->whereKey($siswa->kelas_id))
            ->orderBy('mulai')
            ->get();

        $peserta = PesertaUjian::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('jadwal_ujian_id', $jadwal->pluck('id'))
            ->get()
            ->keyBy('jadwal_ujian_id');

        $riwayat = PesertaUjian::query()
            ->with(['jadwalUjian.paketSoal.mapel'])
            ->where('siswa_id', $siswa->id)
            ->where('status', StatusPesertaUjian::Selesai->value)
            ->latest('waktu_selesai')
            ->limit(10)
            ->get();

        return view('siswa.dashboard', compact('siswa', 'jadwal', 'peserta', 'riwayat'));
    }
}
