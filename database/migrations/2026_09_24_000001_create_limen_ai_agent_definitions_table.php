<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_agent_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model');
            $table->string('provider');
            $table->longText('instructions');
            $table->json('skills')->nullable();
            $table->json('tools')->nullable();
            $table->json('knowledge')->nullable();
            $table->json('memory')->nullable();
            $table->json('authorization')->nullable();
            $table->json('output')->nullable();
            $table->json('limits')->nullable();
            $table->string('agent_class')->nullable();
            $table->string('version')->default('1.0.0');
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index('enabled');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_agent_definitions');
    }
};
