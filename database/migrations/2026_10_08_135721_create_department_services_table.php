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
        Schema::create('department_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // FontAwesome class
            $table->string('link_type')->default('form'); // form, external, page
            $table->string('link_url')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['department_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_services');
    }
};
