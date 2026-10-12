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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('resource_categories')->onDelete('cascade');
            $table->enum('file_type', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'video', 'link']);
            $table->string('file_path')->nullable();
            $table->string('file_size')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->integer('download_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category_id', 'is_active']);
            $table->index('department_id');
        });

        // Index plein-texte : fonctionnalité propre à PostgreSQL/MySQL. SQLite
        // (utilisé par la suite de tests) ne le gère pas, on le saute alors.
        if (in_array(Schema::getConnection()->getDriverName(), ['pgsql', 'mysql'], true)) {
            Schema::table('resources', function (Blueprint $table) {
                $table->fullText(['title', 'description']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
