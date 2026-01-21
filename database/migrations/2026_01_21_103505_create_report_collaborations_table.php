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
        Schema::create('report_collaboration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaboration_id')->constrained('collaborations')->onDelete('cascade');
            $table->enum('reason', ['spam', 'abuse', 'harassment', 'abusive', 'inappropriate', 'porn', 'pretend to be other people']);
            $table->timestamps();

            $table->index(['collaboration_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_collaboration');
    }
};
