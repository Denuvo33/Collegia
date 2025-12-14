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
        Schema::table('event', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('status');
            $table->foreignId('user_pay_transaction_id')->nullable()->constrained('transactions')->onDelete('set null')->after('is_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event', function (Blueprint $table) {
            $table->dropForeign(['user_pay_transaction_id']);
            $table->dropColumn(['is_visible', 'user_pay_transaction_id']);
        });
    }
};
