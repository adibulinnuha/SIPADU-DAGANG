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

            $table->string('jenis');

            $table->string('kode');

            $table->string('nomor_register')
                ->nullable();

            $table->string('nomor_setor')
                ->nullable();

            $table->decimal('nominal', 15, 2)
                ->default(0);

            $table->string('status')
                ->default('draft');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bendel_documents');
    }
};
