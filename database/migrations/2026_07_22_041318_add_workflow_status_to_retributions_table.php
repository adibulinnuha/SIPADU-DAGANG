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
        Schema::table('retributions', function (Blueprint $table) {
            $table->string('status')
                ->default('draft')
                ->after('notes');

            $table->timestamp('submitted_at')
                ->nullable()
                ->after('status');

            $table->foreignId('submitted_by')
                ->nullable()
                ->after('submitted_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')
                ->nullable()
                ->after('submitted_by');

            $table->foreignId('verified_by')
                ->nullable()
                ->after('verified_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable()
                ->after('verified_by');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('locked_at')
                ->nullable()
                ->after('approved_by');

            $table->foreignId('locked_by')
                ->nullable()
                ->after('locked_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('status');
            $table->index('retribution_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['retribution_date']);

            $table->dropConstrainedForeignId('submitted_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('locked_by');

            $table->dropColumn([
                'status',
                'submitted_at',
                'submitted_by',
                'verified_at',
                'verified_by',
                'approved_at',
                'approved_by',
                'locked_at',
                'locked_by',
            ]);
        });
    }
};
