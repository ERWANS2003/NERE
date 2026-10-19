<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createIfMissing('tickets', [
            'id', 'number', 'department_id', 'requester_id', 'assignee_id',
            'subject', 'description', 'category', 'priority', 'status',
            'resolved_at', 'closed_at', 'created_at', 'updated_at',
        ], function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 180);
            $table->text('description');
            $table->string('category', 40);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('new');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'status', 'priority']);
            $table->index(['requester_id', 'created_at']);
            $table->index(['assignee_id', 'status']);
        });

        $this->createIfMissing('ticket_comments', [
            'id', 'ticket_id', 'user_id', 'body', 'is_internal', 'created_at', 'updated_at',
        ], function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        $this->createIfMissing('ticket_attachments', [
            'id', 'ticket_id', 'ticket_comment_id', 'user_id', 'disk', 'path',
            'original_name', 'mime_type', 'size', 'created_at', 'updated_at',
        ], function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_comment_id')->nullable()->constrained('ticket_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index(['ticket_id', 'ticket_comment_id']);
        });

        $this->createIfMissing('ticket_activities', [
            'id', 'ticket_id', 'user_id', 'event', 'changes', 'created_at', 'updated_at',
        ], function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40);
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });

        $this->createIfMissing('notifications', [
            'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at',
        ], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_activities');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_comments');
        Schema::dropIfExists('tickets');
    }

    /**
     * Adopt an already-created table only when it has the columns this migration
     * expects. This allows a deployment to recover from an earlier partial run
     * without dropping ticket data or silently accepting an incompatible schema.
     *
     * @param  list<string>  $columns
     */
    private function createIfMissing(string $name, array $columns, Closure $definition): void
    {
        if (Schema::hasTable($name)) {
            if (! Schema::hasColumns($name, $columns)) {
                throw new RuntimeException("La table [{$name}] existe déjà mais son schéma est incomplet; vérifiez-la avant de relancer les migrations.");
            }

            return;
        }

        Schema::create($name, $definition);
    }
};
