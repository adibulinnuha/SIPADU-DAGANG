<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->string('nomor_setor')->nullable()->after('retribution_date');
            $table->string('collector_name')->nullable()->after('recorded_by');
            $table->string('billing_status')->default('draft')->after('payment_method');
            $table->date('verified_date')->nullable()->after('billing_status');
        });
    }

    public function down(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_setor',
                'collector_name',
                'billing_status',
                'verified_date',
            ]);
        });
    }
};
