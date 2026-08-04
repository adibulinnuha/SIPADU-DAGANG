<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an `entry_type` column to distinguish the two ERET worksheet tables.
     *
     * The official ERET Excel template contains two independent sections:
     *   - Retribusi Manual   (entry_type = 'manual')
     *   - E-Retribusi        (entry_type = 'eret')
     *
     * Each section has its own rows, subtotals and calculations. Only the final
     * grand total combines values across both sections. The default value of
     * 'manual' preserves backward compatibility with existing records created
     * before this migration.
     */
    public function up(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->string('entry_type')
                ->default('manual')
                ->after('notes');

            $table->index('entry_type');
        });
    }

    public function down(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->dropIndex(['entry_type']);
            $table->dropColumn('entry_type');
        });
    }
};

