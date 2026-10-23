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
        Schema::create('pengaturan_sekolah', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah');
            $table->string('jenjang');
            $table->string('npsn')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('logo')->nullable();
            $table->string('nama_kepala_sekolah')->nullable();
            $table->string('nip_kepala_sekolah')->nullable();
            $table->unsignedTinyInteger('batas_pelanggaran')->default(5);
            $table->boolean('auto_tangguhkan')->default(true);
            $table->unsignedTinyInteger('idle_timeout_menit')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_sekolah');
    }
};
