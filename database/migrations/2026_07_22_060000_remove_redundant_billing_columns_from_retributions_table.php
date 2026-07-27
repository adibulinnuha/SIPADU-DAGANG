<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->dropColumn([
                'billing_status',
                'verified_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('retributions', function (Blueprint $table) {
            $table->string('billing_status')
                ->default('draft')
                ->after('payment_method');

            $table->date('verified_date')
                ->nullable()
                ->after('billing_status');
        });
    }
};
