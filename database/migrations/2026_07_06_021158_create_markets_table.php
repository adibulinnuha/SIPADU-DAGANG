<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bendel_documents', function (Blueprint $table) {

            $table->id();

            $table->foreignId('bendel_id')
                ->constrained()
                ->cascadeOnDelete();

            // Retribusi Harian, Kebersihan, Listrik, MCK, dll.
            $table->string('jenis');

            // H, E, C, D
            $table->string('kode', 5);

            // Nomor dari Bendahara
            $table->integer('nomor_register')->nullable();

            // Nomor setor bank
            $table->string('nomor_setor')->nullable();

            // Total nominal dokumen
            $table->decimal('nominal', 15, 2)->default(0);

            $table->enum('status', [
                'draft',
                'menunggu_nomor',
                'selesai'
            ])->default('draft');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bendel_documents');
    }
};