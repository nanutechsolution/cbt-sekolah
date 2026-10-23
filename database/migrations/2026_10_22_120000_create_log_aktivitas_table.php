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
        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pengguna_nama')->nullable();
            $table->string('aksi', 50);
            $table->string('entitas_type');
            $table->unsignedBigInteger('entitas_id');
            $table->string('keterangan')->nullable();
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('aksi');
            $table->index('pengguna_id');
            $table->index(['entitas_type', 'entitas_id'], 'log_aktivitas_entitas_index');
            $table->index('created_at', 'log_aktivitas_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
