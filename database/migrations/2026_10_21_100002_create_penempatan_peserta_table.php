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
        Schema::create('penempatan_peserta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->foreignId('jenis_ujian_id')->constrained('jenis_ujian')->restrictOnDelete();
            $table->foreignId('ruang_id')->constrained('ruang')->restrictOnDelete();
            $table->foreignId('sesi_id')->constrained('sesi')->restrictOnDelete();
            $table->string('no_peserta');
            $table->timestamps();

            $table->unique(['siswa_id', 'tahun_ajaran_id', 'jenis_ujian_id'], 'penempatan_siswa_tahun_jenis_unique');
            $table->unique(['tahun_ajaran_id', 'jenis_ujian_id', 'no_peserta'], 'penempatan_tahun_jenis_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penempatan_peserta');
    }
};
