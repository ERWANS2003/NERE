<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('link_token', 64)->unique();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamp('registration_closes_at')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('menu_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('menu_campaigns')->cascadeOnDelete();
            $table->date('date');
            $table->text('menu_one');
            $table->text('menu_two');
            $table->string('dessert');
            $table->timestamps();

            $table->unique(['campaign_id', 'date']);
        });

        Schema::create('menu_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('menu_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_name');
            $table->string('employee_identifier');
            $table->string('department', 150);
            $table->timestamps();

            $table->unique(['campaign_id', 'employee_identifier']);
            $table->index(['campaign_id', 'department']);
        });

        Schema::create('menu_choices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->constrained('menu_registrations')->cascadeOnDelete();
            $table->foreignId('menu_day_id')->constrained('menu_days')->cascadeOnDelete();
            $table->unsignedTinyInteger('selected_menu');
            $table->timestamps();

            $table->unique(['registration_id', 'menu_day_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_choices');
        Schema::dropIfExists('menu_registrations');
        Schema::dropIfExists('menu_days');
        Schema::dropIfExists('menu_campaigns');
    }
};
