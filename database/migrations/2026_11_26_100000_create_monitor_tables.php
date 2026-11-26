<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Groupes imbriqués : les réglages vides sont hérités du groupe parent,
        // puis de la configuration (config/monitor.php).
        Schema::create('monitor_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('monitor_groups')->nullOnDelete();
            $table->string('name', 120);
            $table->unsignedInteger('interval_seconds')->nullable();
            $table->unsignedInteger('notify_delay_minutes')->nullable();
            $table->unsignedInteger('notify_repeat_minutes')->nullable();
            $table->unsignedInteger('escalate_after_minutes')->nullable();
            $table->text('snmp_community')->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('monitor_groups')->restrictOnDelete();
            $table->foreignId('parent_device_id')->nullable()->constrained('monitor_devices')->nullOnDelete();
            $table->string('name', 120);
            $table->string('host', 253)->unique();
            $table->string('type', 20);
            $table->unsignedInteger('interval_seconds')->nullable();
            $table->unsignedInteger('notify_delay_minutes')->nullable();
            $table->unsignedInteger('notify_repeat_minutes')->nullable();
            $table->unsignedInteger('escalate_after_minutes')->nullable();
            $table->text('snmp_community')->nullable();
            $table->boolean('paused')->default(false);
            $table->timestamp('paused_until')->nullable();
            $table->string('pause_reason', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('monitor_devices')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('name', 120);
            $table->unsignedInteger('port')->nullable();
            $table->string('parameter', 255)->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('interval_seconds')->nullable();
            $table->double('warn_above')->nullable();
            $table->double('error_above')->nullable();
            $table->double('warn_below')->nullable();
            $table->double('error_below')->nullable();
            $table->boolean('paused')->default(false);
            $table->timestamp('paused_until')->nullable();
            $table->string('pause_reason', 255)->nullable();
            $table->string('status', 12)->default('unknown');
            $table->double('last_value')->nullable();
            $table->string('last_message', 255)->nullable();
            $table->json('state')->nullable();
            $table->unsignedSmallInteger('failures')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('acknowledged_until')->nullable();
            $table->string('acknowledgement', 255)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->boolean('escalated')->default(false);
            $table->foreignId('ticket_id')->nullable()->constrained('it_support_tickets')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('monitor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->boolean('ok');
            $table->double('value')->nullable();
            $table->index(['sensor_id', 'checked_at']);
        });

        Schema::create('monitor_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->timestamp('hour');
            $table->unsignedSmallInteger('samples');
            $table->unsignedSmallInteger('failures');
            $table->double('avg_value')->nullable();
            $table->double('min_value')->nullable();
            $table->double('max_value')->nullable();
            $table->unique(['sensor_id', 'hour']);
        });

        Schema::create('monitor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 12)->nullable();
            $table->string('to_status', 12)->nullable();
            $table->string('message', 255)->nullable();
            $table->timestamp('created_at');
            $table->index('created_at');
            $table->index(['sensor_id', 'created_at']);
        });

        Schema::create('monitor_maps', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('background_path')->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_map_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('map_id')->constrained('monitor_maps')->cascadeOnDelete();
            $table->foreignId('device_id')->constrained('monitor_devices')->cascadeOnDelete();
            $table->decimal('x', 5, 2);
            $table->decimal('y', 5, 2);
            $table->unique(['map_id', 'device_id']);
        });

        Schema::create('monitor_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('monitor_groups')->nullOnDelete();
            $table->string('cidr', 18);
            $table->string('status', 12);
            $table->json('results')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['monitor_discoveries', 'monitor_map_items', 'monitor_maps', 'monitor_events', 'monitor_hourly', 'monitor_readings', 'monitor_sensors', 'monitor_devices', 'monitor_groups'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
