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
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting', 'birthday', 'arrival', 'event', 'training'])->default('event');
            $table->date('event_date');
            $table->time('event_time')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Pour anniversaires/arrivées
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('all_day')->default(true);
            $table->timestamps();
            
            $table->index(['event_date', 'type']);
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
