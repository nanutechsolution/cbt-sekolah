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
        Schema::create('paket_soal', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->foreignId('mapel_id')->constrained('mapel')->restrictOnDelete();
            $table->foreignId('jenis_ujian_id')->constrained('jenis_ujian')->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->restrictOnDelete();
            $table->unsignedTinyInteger('tingkat');
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->boolean('acak_soal')->default(true);
            $table->boolean('acak_opsi')->default(true);
            $table->boolean('tampilkan_nilai')->default(true);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mapel_id', 'tingkat']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_soal');
    }
};
