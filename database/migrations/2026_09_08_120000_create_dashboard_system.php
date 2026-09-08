<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dashboard personnalisables par utilisateur
        Schema::create('dashboard_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('layout'); // Configuration grid des widgets
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->unique('user_id');
        });

        // Widgets personnalisés créés par les utilisateurs
        Schema::create('custom_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // chart, list, metric, custom
            $table->json('config'); // Configuration du widget
            $table->json('data_source'); // Source de données
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // Configuration automation/workflows
        Schema::create('workflow_automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_event'); // ticket.created, asset.maintenance_due, etc.
            $table->json('conditions'); // If-then conditions
            $table->json('actions'); // Actions à exécuter
            $table->boolean('is_active')->default(true);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Log des exécutions automation
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('workflow_automations')->onDelete('cascade');
            $table->string('status'); // success, failed, skipped
            $table->json('context'); // Données du déclencheur
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at');
            $table->index(['automation_id', 'executed_at']);
        });

        // Catalogue de services
        Schema::create('service_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories');
            $table->json('request_form'); // Configuration formulaire dynamique
            $table->json('approval_workflow')->nullable(); // Workflow d'approbation
            $table->boolean('requires_approval')->default(false);
            $table->integer('estimated_time')->nullable(); // en minutes
            $table->foreignId('default_assignee_id')->nullable()->constrained('users');
            $table->foreignId('default_team_id')->nullable()->constrained('teams');
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Demandes depuis le catalogue
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('service_catalog');
            $table->foreignId('ticket_id')->nullable()->constrained('tickets');
            $table->foreignId('requester_id')->constrained('users');
            $table->json('form_data'); // Données du formulaire
            $table->string('status'); // pending, approved, rejected, completed
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamps();
        });

        // Base de connaissances améliorée
        Schema::create('kb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('kb_categories')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('kb_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->default('#6B7280');
            $table->timestamps();
        });

        Schema::create('kb_article_tags', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained('knowledge_articles')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('kb_tags')->onDelete('cascade');
            $table->primary(['article_id', 'tag_id']);
        });

        // Métriques et analytics
        Schema::create('metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name');
            $table->string('metric_type'); // gauge, counter, histogram
            $table->decimal('value', 12, 2);
            $table->json('dimensions')->nullable(); // Tags/dimensions
            $table->timestamp('recorded_at');
            $table->index(['metric_name', 'recorded_at']);
        });

        // Notifications personnalisées
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel'); // email, sms, push, slack
            $table->string('event_type'); // ticket.assigned, sla.breach, etc.
            $table->boolean('enabled')->default(true);
            $table->json('config')->nullable(); // Config spécifique
            $table->timestamps();
            
            $table->unique(['user_id', 'channel', 'event_type']);
        });

        // Templates de tickets pour faciliter la création
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories');
            $table->foreignId('priority_id')->nullable()->constrained('ticket_priorities');
            $table->json('default_fields'); // Valeurs pré-remplies
            $table->json('required_fields')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_templates');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('metric_snapshots');
        Schema::dropIfExists('kb_article_tags');
        Schema::dropIfExists('kb_tags');
        Schema::dropIfExists('kb_categories');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_catalog');
        Schema::dropIfExists('automation_logs');
        Schema::dropIfExists('workflow_automations');
        Schema::dropIfExists('custom_widgets');
        Schema::dropIfExists('dashboard_layouts');
    }
};
