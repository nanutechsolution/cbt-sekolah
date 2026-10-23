<?php

namespace App\Services;

use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

class TahunAjaranService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?TahunAjaran $tahunAjaran = null): TahunAjaran
    {
        return DB::transaction(function () use ($data, $tahunAjaran): TahunAjaran {
            $tahunAjaran ??= new TahunAjaran;
            $isAktif = (bool) ($data['is_aktif'] ?? false);

            if ($isAktif) {
                $this->deactivateOthers($tahunAjaran);
            }

            $tahunAjaran->fill($data)->save();

            return $tahunAjaran->refresh();
        });
    }

    public function activate(TahunAjaran $tahunAjaran): TahunAjaran
    {
        return DB::transaction(function () use ($tahunAjaran): TahunAjaran {
            $this->deactivateOthers($tahunAjaran);

            $tahunAjaran->update(['is_aktif' => true]);

            return $tahunAjaran->refresh();
        });
    }

    public function active(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->where('is_aktif', true)
            ->first();
    }

    private function deactivateOthers(TahunAjaran $except): void
    {
        TahunAjaran::query()
            ->when($except->exists, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->where('is_aktif', true)
            ->update(['is_aktif' => false]);
    }
}
