<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('host', 253)->unique();
            $table->string('type', 20);
            $table->string('site', 100);
            $table->boolean('paused')->default(false);
            $table->timestamps();
            $table->index(['site', 'name']);
        });

        Schema::create('monitor_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('monitor_devices')->cascadeOnDelete();
            $table->string('kind', 10);
            $table->string('name', 120);
            $table->unsignedInteger('port')->nullable();
            $table->string('path', 255)->nullable();
            $table->unsignedInteger('warning_ms')->nullable();
            $table->boolean('paused')->default(false);
            $table->string('status', 10)->default('unknown');
            $table->unsignedInteger('last_ms')->nullable();
            $table->string('last_message', 255)->nullable();
            $table->unsignedSmallInteger('failures')->default(0);
            $table->timestamp('checked_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgement', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('monitor_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->boolean('ok');
            $table->unsignedInteger('ms')->nullable();
            $table->index(['sensor_id', 'checked_at']);
        });

        Schema::create('monitor_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->timestamp('hour');
            $table->unsignedSmallInteger('samples');
            $table->unsignedSmallInteger('failures');
            $table->unsignedInteger('avg_ms')->nullable();
            $table->unsignedInteger('max_ms')->nullable();
            $table->unique(['sensor_id', 'hour']);
        });

        Schema::create('monitor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sensor_id')->constrained('monitor_sensors')->cascadeOnDelete();
            $table->string('from_status', 10);
            $table->string('to_status', 10);
            $table->string('message', 255)->nullable();
            $table->timestamp('created_at');
            $table->index(['sensor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_events');
        Schema::dropIfExists('monitor_hourly');
        Schema::dropIfExists('monitor_readings');
        Schema::dropIfExists('monitor_sensors');
        Schema::dropIfExists('monitor_devices');
    }
};
