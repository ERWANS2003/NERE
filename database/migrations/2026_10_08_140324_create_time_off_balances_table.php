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
        Schema::create('time_off_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('policy_id')->constrained('time_off_policies')->onDelete('cascade');
            $table->integer('year');
            $table->decimal('total_days', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('remaining_days', 5, 1)->default(0);
            $table->decimal('carry_over_days', 5, 1)->default(0);
            $table->timestamps();
            
            $table->unique(['user_id', 'policy_id', 'year']);
            $table->index(['user_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_off_balances');
    }
};
