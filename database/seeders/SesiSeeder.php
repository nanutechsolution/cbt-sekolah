<?php

namespace Database\Seeders;

use App\Models\Sesi;
use Illuminate\Database\Seeder;

class SesiSeeder extends Seeder
{
    /**
     * Seed the exam sessions for local development.
     */
    public function run(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        foreach ([
            ['nama' => 'Pagi', 'jam_mulai' => '07:30', 'jam_selesai' => '09:30'],
            ['nama' => 'Siang', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00'],
        ] as $data) {
            $sesi = Sesi::withTrashed()->firstOrCreate(
                ['nama' => $data['nama']],
                [
                    'jam_mulai' => $data['jam_mulai'],
                    'jam_selesai' => $data['jam_selesai'],
                ],
            );

            if ($sesi->trashed()) {
                $sesi->restore();
            }
        }
    }
}
