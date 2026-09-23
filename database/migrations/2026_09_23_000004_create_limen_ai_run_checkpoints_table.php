<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('limen_ai_run_checkpoints', function (Blueprint $table) {
            $table->uuid('run_id')->primary();
            $table->unsignedInteger('step')->default(0);
            $table->json('state');
            $table->timestamps();

            $table->foreign('run_id')
                ->references('id')
                ->on('limen_ai_runs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('limen_ai_run_checkpoints');
    }
};
