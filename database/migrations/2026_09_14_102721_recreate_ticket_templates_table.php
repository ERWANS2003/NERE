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
        // Drop table if exists with all constraints
        Schema::dropIfExists('ticket_templates');

        // Recreate table with all required columns including 'actif'
        Schema::create('ticket_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->onDelete('set null');
            $table->foreignId('priorite_id')->nullable()->constrained('ticket_priorities')->onDelete('set null');
            $table->string('titre_template');
            $table->longText('description_template');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index('created_by');
            $table->index('actif');
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_templates');
    }
};
