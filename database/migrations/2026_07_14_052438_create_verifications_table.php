<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {

            $table->id();

            $table->foreignId('retribution_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('nomor_setor')
                ->nullable();

            $table->date('tanggal_verifikasi')
                ->nullable();

            $table->enum('status', [
                'Pending',
                'Terverifikasi'
            ])->default('Pending');

            $table->text('catatan')
                ->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};