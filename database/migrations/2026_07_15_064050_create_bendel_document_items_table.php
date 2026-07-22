<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bendel_document_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('bendel_document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('market_id')
                ->constrained()
                ->cascadeOnDelete();

            // KIOS, LOS, DT, NON DT, MCK, LISTRIK, dll.
            $table->string('jenis_retribusi');

            $table->decimal('nominal', 15, 2)->default(0);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bendel_document_items');
    }
};
