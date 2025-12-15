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
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('transaction_type', ['event_creation', 'event_promotion'])->default('event_creation')->after('order_id');
            $table->integer('promotion_days')->nullable()->after('event_id');
            $table->date('promotion_start_date')->nullable()->after('promotion_days');
            $table->date('promotion_end_date')->nullable()->after('promotion_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['transaction_type', 'promotion_days', 'promotion_start_date', 'promotion_end_date']);
        });
    }
};
