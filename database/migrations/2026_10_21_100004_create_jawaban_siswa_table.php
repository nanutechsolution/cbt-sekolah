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
        Schema::create('jawaban_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_ujian_id')->constrained('peserta_ujian')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->json('jawaban')->nullable();
            $table->boolean('ragu')->default(false);
            $table->boolean('is_benar')->nullable();
            $table->decimal('skor', 5, 2)->nullable();
            $table->unsignedTinyInteger('jumlah_putar_audio')->default(0);
            $table->dateTime('dijawab_pada')->nullable();
            $table->timestamps();

            $table->unique(['peserta_ujian_id', 'soal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jawaban_siswa');
    }
};
