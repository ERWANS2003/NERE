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
        Schema::create('time_off_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->unique(); // vacation, sick, personal, holiday
            $table->text('description')->nullable();
            $table->integer('annual_allocation'); // Jours alloués par an
            $table->integer('carry_over_limit')->default(0); // Jours reportables
            $table->boolean('requires_approval')->default(true);
            $table->integer('advance_notice_days')->default(0); // Préavis requis
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_off_policies');
    }
};
