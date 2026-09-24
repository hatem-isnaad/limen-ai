<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('limen_ai_usage_records', function (Blueprint $table) {
            $table->uuid('conversation_id')->nullable()->after('run_id');
            $table->uuid('message_id')->nullable()->after('conversation_id');

            $table->index('conversation_id');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::table('limen_ai_usage_records', function (Blueprint $table) {
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['message_id']);
            $table->dropColumn(['conversation_id', 'message_id']);
        });
    }
};
