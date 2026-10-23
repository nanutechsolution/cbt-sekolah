<?php

namespace App\Http\Controllers;

use App\Enums\StatusPesertaUjian;
use App\Models\JadwalUjian;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CetakController extends Controller
{
    public function kartu(JadwalUjian $jadwal)
    {
        $this->otorisasi($jadwal);

        $jadwal->load(['paketSoal.mapel', 'paketSoal.jenisUjian', 'paketSoal.tahunAjaran', 'kelas']);

        $siswa = $this->siswaPadaJadwal($jadwal)->get();
        $penempatan = $this->penempatanUntuk($jadwal, $siswa);

        return Pdf::loadView('pdf.kartu-peserta', compact('jadwal', 'siswa', 'penempatan'))
            ->setPaper('a4', 'portrait')
            ->download('kartu-peserta-'.$jadwal->id.'.pdf');
    }

    public function daftarHadir(Request $request, JadwalUjian $jadwal)
    {
        $this->otorisasi($jadwal);

        $jadwal->load(['paketSoal.mapel', 'paketSoal.jenisUjian', 'paketSoal.tahunAjaran', 'kelas']);

        $siswa = $this->siswaPadaJadwal($jadwal)
            ->when($request->integer('kelas_id'), fn (Builder $query, int $kelasId) => $query->where('kelas_id', $kelasId))
            ->get();

        $penempatan = $this->penempatanUntuk($jadwal, $siswa);

        return Pdf::loadView('pdf.daftar-hadir', compact('jadwal', 'siswa', 'penempatan'))
            ->setPaper('a4', 'landscape')
            ->download('daftar-hadir-'.$jadwal->id.'.pdf');
    }

    public function beritaAcara(JadwalUjian $jadwal)
    {
        $this->otorisasi($jadwal);

        $jadwal->load(['paketSoal.mapel', 'paketSoal.jenisUjian', 'paketSoal.tahunAjaran', 'kelas']);

        $siswa = $this->siswaPadaJadwal($jadwal)->get();
        $peserta = PesertaUjian::query()
            ->where('jadwal_ujian_id', $jadwal->id)
            ->whereIn('siswa_id', $siswa->pluck('id'))
            ->get()
            ->keyBy('siswa_id');

        return Pdf::loadView('pdf.berita-acara', compact('jadwal', 'siswa', 'peserta'))
            ->setPaper('a4', 'portrait')
            ->download('berita-acara-'.$jadwal->id.'.pdf');
    }

    private function otorisasi(JadwalUjian $jadwal): void
    {
        $user = Auth::user();

        abort_unless($user !== null && $user->can('view', $jadwal), 403);
    }

    /**
     * @return Builder<Siswa>
     */
    private function siswaPadaJadwal(JadwalUjian $jadwal): Builder
    {
        return Siswa::query()
            ->with(['kelas', 'agama'])
            ->whereIn('kelas_id', $jadwal->kelas->pluck('id'))
            ->where('is_aktif', true)
            ->orderBy('nama');
    }

    /**
     * @param  Collection<int, Siswa>  $siswa
     * @return Collection<int, \App\Models\PenempatanPeserta>
     */
    private function penempatanUntuk(JadwalUjian $jadwal, Collection $siswa): Collection
    {
        if ($siswa->isEmpty()) {
            return collect();
        }

        return \App\Models\PenempatanPeserta::query()
            ->whereIn('siswa_id', $siswa->pluck('id'))
            ->where('tahun_ajaran_id', $jadwal->paketSoal->tahun_ajaran_id)
            ->where('jenis_ujian_id', $jadwal->paketSoal->jenis_ujian_id)
            ->get()
            ->keyBy('siswa_id');
    }
}
