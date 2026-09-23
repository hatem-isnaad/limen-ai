<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_memories', function (Blueprint $table) {
            $table->id();
            $table->string('scope');
            $table->string('scope_id');
            $table->string('agent_key')->nullable();
            $table->string('memory_key');
            $table->json('value');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'scope_id', 'agent_key', 'memory_key'], 'limen_ai_memories_unique');
            $table->index(['scope', 'scope_id']);
            $table->index('agent_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_memories');
    }
};
