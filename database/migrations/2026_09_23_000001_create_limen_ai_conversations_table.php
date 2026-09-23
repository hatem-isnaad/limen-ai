<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_key');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_token')->nullable();
            $table->string('title')->nullable();
            $table->json('metadata')->nullable();
            $table->string('state')->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('agent_key');
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_conversations');
    }
};
