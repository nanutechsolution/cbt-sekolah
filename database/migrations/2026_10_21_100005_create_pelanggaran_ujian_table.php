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
        Schema::create('pelanggaran_ujian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peserta_ujian_id')->constrained('peserta_ujian')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index('peserta_ujian_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggaran_ujian');
    }
};
