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
        // The table already exists in some databases (created out of band),
        // and 2026_09_14_102721_recreate_ticket_templates_table.php owns the
        // final shape. Creating here unconditionally aborted the whole pending
        // migration queue with a duplicate-table error.
        if (Schema::hasTable('ticket_templates')) {
            return;
        }

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
