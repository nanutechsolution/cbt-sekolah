<?php

namespace App\Http\Controllers;

use App\Services\PengaturanSekolahService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{
    public function __construct(
        private readonly PengaturanSekolahService $pengaturanSekolahService,
    ) {}

    public function __invoke(): View
    {
        try {
            $pengaturan = $this->pengaturanSekolahService->find();
        } catch (Throwable) {
            $pengaturan = null;
        }

        $namaSekolah = trim((string) $pengaturan?->nama_sekolah);

        if ($namaSekolah === '') {
            $namaSekolah = (string) config('app.name');
        }

        if ($namaSekolah === '') {
            $namaSekolah = 'CBT Sekolah';
        }

        $logoUrl = null;

        if (filled($pengaturan?->logo)) {
            try {
                $logoPath = ltrim((string) $pengaturan->logo, '/');
                $logoPublicPath = public_path('storage/'.$logoPath);

                if (is_file($logoPublicPath)) {
                    $logoUrl = asset('storage/'.$logoPath);
                }
            } catch (Throwable) {
                $logoUrl = null;
            }
        }

        $inisial = Str::upper(Str::substr($namaSekolah, 0, 1));

        if ($inisial === '') {
            $inisial = 'C';
        }

        return view('welcome', [
            'namaSekolah' => $namaSekolah,
            'logoUrl' => $logoUrl,
            'inisial' => $inisial,
        ]);
    }
}
