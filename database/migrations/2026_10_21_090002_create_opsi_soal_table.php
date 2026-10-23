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
        Schema::create('opsi_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->string('label', 5);
            $table->text('teks');
            $table->string('gambar')->nullable();
            $table->boolean('is_benar')->default(false);
            $table->unsignedSmallInteger('urutan');
            $table->text('pasangan')->nullable();
            $table->timestamps();

            $table->index('soal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opsi_soal');
    }
};
