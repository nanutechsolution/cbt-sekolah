<?php

namespace App\Http\Controllers;

use App\Models\JadwalUjian;
use App\Services\AnalisisButirExportService;
use App\Services\AnalisisButirService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalisisButirController extends Controller
{
    public function export(
        Request $request,
        JadwalUjian $jadwal,
        AnalisisButirService $analisisButirService,
        AnalisisButirExportService $exportService,
    ): BinaryFileResponse {
        abort_unless($request->user()?->can('view', $jadwal), 403);

        $jadwal->loadMissing(['paketSoal.mapel']);
        $path = $exportService->buat($jadwal, $analisisButirService->analisis($jadwal));

        return response()
            ->download($path, 'analisis-butir-'.$jadwal->id.'.xlsx')
            ->deleteFileAfterSend();
    }
}
