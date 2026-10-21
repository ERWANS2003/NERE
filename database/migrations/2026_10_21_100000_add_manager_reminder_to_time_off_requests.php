<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->timestamp('manager_reminder_sent_at')->nullable();
            $table->index(
                ['status', 'manager_reminder_sent_at', 'created_at'],
                'time_off_manager_reminder_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('time_off_requests', function (Blueprint $table) {
            $table->dropIndex('time_off_manager_reminder_idx');
            $table->dropColumn('manager_reminder_sent_at');
        });
    }
};
