<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bendels', function (Blueprint $table) {

            $table->id();

            // Tanggal pendapatan seluruh bendel
            $table->date('tanggal_pendapatan');

            // Tanggal setor ke bendahara
            $table->date('tanggal_setor');

            // Draft -> Menunggu Nomor -> Selesai -> Arsip
            $table->enum('status', [
                'draft',
                'menunggu_nomor',
                'selesai',
                'arsip',
            ])->default('draft');

            // Operator pembuat bendel
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('keterangan')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bendels');
    }
};
