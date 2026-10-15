<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_location_id')->constrained('meal_locations')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->timestamp('response_deadline');
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['meal_location_id', 'week_start']);
            $table->index(['meal_location_id', 'status', 'week_start']);
        });

        Schema::create('weekly_menu_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_menu_id')->constrained('weekly_menus')->cascadeOnDelete();
            $table->date('menu_date');
            $table->string('day_name', 20);
            $table->text('menu_1');
            $table->text('menu_2');
            $table->string('dessert', 255);
            $table->timestamps();

            $table->unique(['weekly_menu_id', 'menu_date']);
        });

        Schema::create('weekly_menu_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_menu_day_id')->constrained('weekly_menu_days')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('choice', 20);
            $table->text('comment')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();

            $table->unique(['weekly_menu_day_id', 'user_id']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_menu_choices');
        Schema::dropIfExists('weekly_menu_days');
        Schema::dropIfExists('weekly_menus');
    }
};
