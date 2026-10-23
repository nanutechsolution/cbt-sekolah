<?php

namespace App\Http\Controllers;

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPesertaUjian;
use App\Models\JadwalUjian;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Services\PesertaUjianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiswaUjianController extends Controller
{
    public function __construct(private readonly PesertaUjianService $pesertaUjianService) {}

    public function konfirmasi(JadwalUjian $jadwal): View
    {
        $siswa = $this->siswa();

        $this->pastikanJadwalUntukSiswa($jadwal, $siswa);

        $peserta = PesertaUjian::query()
            ->where('jadwal_ujian_id', $jadwal->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        $jadwal->load(['paketSoal.mapel', 'paketSoal.jenisUjian']);

        return view('siswa.ujian.konfirmasi', compact('siswa', 'jadwal', 'peserta'));
    }

    public function mulai(Request $request, JadwalUjian $jadwal): RedirectResponse
    {
        $siswa = $this->siswa();

        $this->pastikanJadwalUntukSiswa($jadwal, $siswa);

        $pesertaLama = PesertaUjian::query()
            ->where('jadwal_ujian_id', $jadwal->id)
            ->where('siswa_id', $siswa->id)
            ->first();

        try {
            $peserta = $this->pesertaUjianService->mulai(
                $jadwal,
                $siswa,
                $pesertaLama
                    ? $request->session()->get(PesertaUjianService::kunciSesi($pesertaLama->id))
                    : null,
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $request->session()->put(PesertaUjianService::kunciSesi($peserta->id), $peserta->device_token);

        return redirect()->route('siswa.ujian.ruang', $peserta);
    }

    public function ruang(Request $request, PesertaUjian $peserta): View|RedirectResponse
    {
        $siswa = $this->siswa();

        $this->pastikanPesertaMilikSiswa($peserta, $siswa);

        if ($peserta->status === StatusPesertaUjian::Selesai) {
            return redirect()->route('siswa.ujian.hasil', $peserta);
        }

        if ($peserta->status === StatusPesertaUjian::Ditangguhkan) {
            return view('siswa.ujian.ditangguhkan', compact('siswa', 'peserta'));
        }

        if ($peserta->status !== StatusPesertaUjian::Mengerjakan) {
            return redirect()
                ->route('siswa.ujian.konfirmasi', $peserta->jadwal_ujian_id)
                ->withErrors(['ujian' => 'Ujian belum dimulai.']);
        }

        $token = $request->session()->get(PesertaUjianService::kunciSesi($peserta->id));

        abort_unless(
            is_string($token) && hash_equals((string) $peserta->device_token, $token),
            403,
            'Sesi ujian tidak sesuai dengan perangkat ini.',
        );

        return view('siswa.ujian.ruang', compact('siswa', 'peserta'));
    }

    public function hasil(PesertaUjian $peserta): View|RedirectResponse
    {
        $siswa = $this->siswa();

        $this->pastikanPesertaMilikSiswa($peserta, $siswa);

        if ($peserta->status !== StatusPesertaUjian::Selesai) {
            return redirect()->route('siswa.ujian.ruang', $peserta);
        }

        $peserta->load(['jadwalUjian.paketSoal.mapel', 'jadwalUjian.paketSoal.jenisUjian']);

        return view('siswa.ujian.hasil', compact('siswa', 'peserta'));
    }

    private function siswa(): Siswa
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        abort_if($siswa === null, 403);

        return $siswa;
    }

    private function pastikanJadwalUntukSiswa(JadwalUjian $jadwal, Siswa $siswa): void
    {
        abort_unless($jadwal->status === StatusJadwalUjian::Aktif, 404);

        abort_unless(
            $jadwal->kelas()->whereKey($siswa->kelas_id)->exists(),
            403,
            'Anda tidak terdaftar pada jadwal ujian ini.',
        );
    }

    private function pastikanPesertaMilikSiswa(PesertaUjian $peserta, Siswa $siswa): void
    {
        abort_unless($peserta->siswa_id === $siswa->id, 403);
    }
}
