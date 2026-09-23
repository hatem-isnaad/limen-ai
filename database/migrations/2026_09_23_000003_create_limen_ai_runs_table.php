<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->string('agent_key');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('current_step')->default(0);
            $table->unsignedInteger('tool_call_count')->default(0);
            $table->text('final_message')->nullable();
            $table->text('error')->nullable();
            $table->json('messages')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_runs');
    }
};
