<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peserta_ujian', function (Blueprint $table) {
            $table->index('terakhir_aktif', 'peserta_ujian_terakhir_aktif_index');
            $table->index(['jadwal_ujian_id', 'status'], 'peserta_ujian_jadwal_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peserta_ujian', function (Blueprint $table) {
            $table->dropIndex('peserta_ujian_terakhir_aktif_index');
            $table->dropIndex('peserta_ujian_jadwal_status_index');
        });
    }
};
