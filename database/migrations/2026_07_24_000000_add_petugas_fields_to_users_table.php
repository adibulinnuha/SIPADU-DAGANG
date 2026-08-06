<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Master Petugas (Korwil / Juru Pungut) fields to the users table.
 *
 * Backward compatible: all new columns are nullable or have sensible defaults,
 * and the pre-existing `name`, `email`, `password`, `role` columns stay intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('market_id')
                ->nullable()
                ->after('role')
                ->constrained('markets')
                ->nullOnDelete();

            $table->string('nip')->nullable()->after('market_id');
            $table->string('rank')->nullable()->after('nip');
            $table->string('jabatan')->nullable()->after('rank');
            $table->string('phone')->nullable()->after('jabatan');
            $table->text('notes')->nullable()->after('phone');

            $table->boolean('is_active')->default(true)->after('notes');
            $table->boolean('is_juru_pungut')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('market_id');
            $table->dropColumn([
                'nip',
                'rank',
                'jabatan',
                'phone',
                'notes',
                'is_active',
                'is_juru_pungut',
            ]);
        });
    }
};

