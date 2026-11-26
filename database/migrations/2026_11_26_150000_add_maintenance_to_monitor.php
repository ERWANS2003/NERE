<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fenêtre de maintenance hebdomadaire : {"days": [0-6], "start": "22:00", "end": "02:00"}.
        foreach (['monitor_groups', 'monitor_devices'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->json('maintenance')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['monitor_groups', 'monitor_devices'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('maintenance'));
        }
    }
};
