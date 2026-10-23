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
        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_soal_id')->constrained('paket_soal')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor');
            $table->string('tipe');
            $table->longText('pertanyaan');
            $table->string('gambar')->nullable();
            $table->string('audio')->nullable();
            $table->unsignedTinyInteger('audio_maks_putar')->nullable();
            $table->decimal('bobot', 5, 2)->default(1);
            $table->text('pembahasan')->nullable();
            $table->timestamps();

            $table->unique(['paket_soal_id', 'nomor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soal');
    }
};
