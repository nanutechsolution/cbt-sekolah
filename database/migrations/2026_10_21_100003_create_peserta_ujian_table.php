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
        Schema::create('peserta_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_ujian_id')->constrained('jadwal_ujian')->restrictOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->restrictOnDelete();
            $table->string('status')->default('belum');
            $table->dateTime('waktu_mulai')->nullable();
            $table->dateTime('waktu_batas')->nullable();
            $table->dateTime('waktu_selesai')->nullable();
            $table->string('device_token', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('urutan_soal')->nullable();
            $table->json('urutan_opsi')->nullable();
            $table->unsignedSmallInteger('jumlah_pelanggaran')->default(0);
            $table->decimal('nilai', 5, 2)->nullable();
            $table->decimal('nilai_pg', 5, 2)->nullable();
            $table->decimal('nilai_essay', 5, 2)->nullable();
            $table->dateTime('terakhir_aktif')->nullable();
            $table->timestamps();

            $table->unique(['jadwal_ujian_id', 'siswa_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_ujian');
    }
};
