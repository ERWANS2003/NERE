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
        Schema::table('ticket_templates', function (Blueprint $table) {
            // Add actif column if it doesn't exist
            if (!Schema::hasColumn('ticket_templates', 'actif')) {
                $table->boolean('actif')->default(true)->after('priorite_id');
                $table->index('actif');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_templates', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_templates', 'actif')) {
                $table->dropIndex(['actif']);
                $table->dropColumn('actif');
            }
        });
    }
};
