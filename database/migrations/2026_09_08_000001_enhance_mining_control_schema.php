<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enterprise-wide mining control system enhancements
     * Adds audit trails, operational zones, safety matrices, compliance tracking
     */
    public function up(): void
    {
        // Operational Zones - Sites and Areas
        Schema::create('operational_zones', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('code')->unique(); // Zone identifier
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['extraction', 'processing', 'storage', 'maintenance', 'administrative']);
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Equipment/Assets extended tracking
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'operational_zone_id')) {
                $table->foreignId('operational_zone_id')->nullable()->constrained('operational_zones')->nullOnDelete();
                $table->enum('statut_operationnel', ['operational', 'maintenance', 'offline', 'alert'])->default('operational');
                $table->decimal('criticality_score', 3, 1)->nullable(); // 0-10 scale
                $table->timestamp('last_maintenance_at')->nullable();
                $table->timestamp('next_maintenance_at')->nullable();
                $table->json('maintenance_history')->nullable();
            }
        });

        // Safety Incidents and Compliance Tracking
        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_zone_id')->constrained('operational_zones')->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->enum('severity', ['minor', 'moderate', 'serious', 'critical'])->default('moderate');
            $table->enum('statut', ['reported', 'investigating', 'resolved', 'closed'])->default('reported');
            $table->timestamp('incident_at');
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('investigated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('investigation_notes')->nullable();
            $table->json('corrective_actions')->nullable();
            $table->timestamps();
        });

        // SLA and Performance Tracking
        Schema::create('sla_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->string('description');
            $table->enum('module', ['tickets', 'maintenance', 'assets', 'operations']);
            $table->integer('response_time_minutes')->nullable();
            $table->integer('resolution_time_hours')->nullable();
            $table->decimal('uptime_percentage', 5, 2)->default(99.50);
            $table->timestamps();
        });

        // Compliance & Audit Trail
        Schema::create('audit_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // create, update, delete, assign, close, etc.
            $table->string('model_type'); // Ticket, Asset, User, etc.
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('created_at');
        });

        // Department/Budget Tracking for Mining Operations
        Schema::table('departements', function (Blueprint $table) {
            if (!Schema::hasColumn('departements', 'budget_annual')) {
                $table->decimal('budget_annual', 12, 2)->nullable();
                $table->decimal('budget_spent', 12, 2)->default(0);
                $table->enum('status_compliance', ['compliant', 'warning', 'non_compliant'])->default('compliant');
                $table->text('kpis')->nullable();
            }
        });

        // Performance Indicators per Department
        Schema::create('performance_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departement_id')->constrained('departements')->cascadeOnDelete();
            $table->string('nom');
            $table->string('slug');
            $table->decimal('target_value', 8, 2);
            $table->decimal('current_value', 8, 2);
            $table->enum('status', ['on_track', 'warning', 'at_risk'])->default('on_track');
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamps();
            $table->unique(['departement_id', 'slug', 'period_start', 'period_end']);
        });

        // Role-based Access Control Matrix
        Schema::create('role_access_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('operational_zone_id')->nullable()->constrained('operational_zones')->cascadeOnDelete();
            $table->boolean('can_access')->default(false);
            $table->boolean('can_modify')->default(false);
            $table->boolean('can_approve')->default(false);
            $table->text('restrictions')->nullable();
            $table->timestamps();
            $table->unique(['role_id', 'operational_zone_id']);
        });

        // Shift and Schedule Management
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->integer('effectif_nominal');
            $table->timestamps();
        });

        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('operational_zone_id')->constrained('operational_zones')->cascadeOnDelete();
            $table->date('date_assignment');
            $table->enum('statut', ['scheduled', 'completed', 'absent', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
        });

        // Training and Certification Tracking
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description');
            $table->integer('validity_days')->nullable();
            $table->timestamps();
        });

        Schema::create('user_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('certification_id')->constrained('certifications')->cascadeOnDelete();
            $table->date('obtained_at');
            $table->date('expires_at')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Production and Yield Tracking
        Schema::create('production_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_zone_id')->constrained('operational_zones')->cascadeOnDelete();
            $table->date('date_production');
            $table->decimal('quantity_produced', 10, 2);
            $table->string('unit'); // tonnes, litres, etc.
            $table->decimal('quality_percentage', 5, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_records');
        Schema::dropIfExists('user_certifications');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('role_access_matrix');
        Schema::dropIfExists('performance_indicators');
        Schema::dropIfExists('operational_zones');
        Schema::dropIfExists('audit_entries');
        Schema::dropIfExists('sla_metrics');
        Schema::dropIfExists('safety_incidents');
        
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['operational_zone_id']);
            $table->dropColumnIfExists(['operational_zone_id', 'statut_operationnel', 'criticality_score', 'last_maintenance_at', 'next_maintenance_at', 'maintenance_history']);
        });

        Schema::table('departements', function (Blueprint $table) {
            $table->dropColumnIfExists(['budget_annual', 'budget_spent', 'status_compliance', 'kpis']);
        });
    }
};
