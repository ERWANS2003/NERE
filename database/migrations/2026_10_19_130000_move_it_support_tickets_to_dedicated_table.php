<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'tickets' => [
                'it_support_tickets',
                ['id', 'number', 'department_id', 'requester_id', 'assignee_id', 'subject', 'description', 'category', 'priority', 'status', 'resolved_at', 'closed_at', 'created_at', 'updated_at'],
            ],
            'ticket_comments' => [
                'it_support_ticket_comments',
                ['id', 'ticket_id', 'user_id', 'body', 'is_internal', 'created_at', 'updated_at'],
            ],
            'ticket_attachments' => [
                'it_support_ticket_attachments',
                ['id', 'ticket_id', 'ticket_comment_id', 'user_id', 'disk', 'path', 'original_name', 'mime_type', 'size', 'created_at', 'updated_at'],
            ],
            'ticket_activities' => [
                'it_support_ticket_activities',
                ['id', 'ticket_id', 'user_id', 'event', 'changes', 'created_at', 'updated_at'],
            ],
        ];

        foreach ($tables as $source => [$destination, $columns]) {
            if (Schema::hasTable($source)
                && ! Schema::hasTable($destination)
                && Schema::hasColumns($source, $columns)) {
                Schema::rename($source, $destination);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'it_support_ticket_activities' => 'ticket_activities',
            'it_support_ticket_attachments' => 'ticket_attachments',
            'it_support_ticket_comments' => 'ticket_comments',
            'it_support_tickets' => 'tickets',
        ];

        foreach ($tables as $source => $destination) {
            if (Schema::hasTable($source) && ! Schema::hasTable($destination)) {
                Schema::rename($source, $destination);
            }
        }
    }
};
