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
        Schema::create('report_event', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('event_id')->constrained('event')->onDelete('cascade');
            $table->enum('reason', [
                'spam',
                'inappropriate',
                'harassment',
                'abusive',
                'porn',
                'pretend to be other people',
                'other'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_event');
    }
};
