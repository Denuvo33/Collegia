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
            // Drop foreign key constraint on post_id
            $table->dropForeign(['post_id']);

            // Make post_id nullable since it can be collaboration/event/post ID
            $table->unsignedBigInteger('post_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification', function (Blueprint $table) {
            // Restore foreign key
            $table->foreign('post_id')->references('id')->on('post')->onDelete('cascade');
        });
    }
};
