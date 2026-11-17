<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_support_tickets', function (Blueprint $table) {
            $table->json('ai_suggestion')->nullable();
            $table->timestamp('ai_suggested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('it_support_tickets', function (Blueprint $table) {
            $table->dropColumn(['ai_suggestion', 'ai_suggested_at']);
        });
    }
};
