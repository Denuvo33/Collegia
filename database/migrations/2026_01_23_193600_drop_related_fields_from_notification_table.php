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
        Schema::table('notification', function (Blueprint $table) {
            // Drop the polymorphic relationship fields if they exist
            if (Schema::hasColumn('notification', 'related_id')) {
                $table->dropColumn('related_id');
            }
            if (Schema::hasColumn('notification', 'related_type')) {
                $table->dropColumn('related_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
        });
    }
};
