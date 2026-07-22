<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retribution_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('retribution_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('jenis_retribusi');
            $table->integer('quantity')->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'retribution_id',
                'jenis_retribusi',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retribution_items');
    }
};
