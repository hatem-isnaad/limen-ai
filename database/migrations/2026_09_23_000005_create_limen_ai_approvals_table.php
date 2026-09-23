<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('run_id');
            $table->string('tool_key');
            $table->json('payload');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('run_id')
                ->references('id')
                ->on('limen_ai_runs')
                ->cascadeOnDelete();

            $table->index('run_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_approvals');
    }
};
