<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retributions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('market_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('jenis_retribusi');

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('retribution_date');

            $table->decimal('amount', 12, 2);

            $table->string('payment_method')
                ->default('cash');

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'market_id',
                'retribution_date',
            ]);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retributions');
    }
};
