<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_support_tickets', function (Blueprint $table) {
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('response_warning_at')->nullable();
            $table->timestamp('response_warning_sent_at')->nullable();
            $table->timestamp('response_breached_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('resolution_warning_at')->nullable();
            $table->timestamp('resolution_warning_sent_at')->nullable();
            $table->timestamp('resolution_breached_at')->nullable();

            $table->index(['first_responded_at', 'response_warning_at'], 'ticket_response_sla_due_idx');
            $table->index(['status', 'resolution_warning_at'], 'ticket_resolution_sla_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('it_support_tickets', function (Blueprint $table) {
            $table->dropIndex('ticket_response_sla_due_idx');
            $table->dropIndex('ticket_resolution_sla_due_idx');
            $table->dropColumn([
                'first_response_due_at',
                'first_responded_at',
                'response_warning_at',
                'response_warning_sent_at',
                'response_breached_at',
                'resolution_due_at',
                'resolution_warning_at',
                'resolution_warning_sent_at',
                'resolution_breached_at',
            ]);
        });
    }
};
