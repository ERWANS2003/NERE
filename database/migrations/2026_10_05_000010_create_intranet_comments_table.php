<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('intranet_submissions')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->text('body');
            // is_internal : visible seulement par techniciens / directeur
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_comments');
    }
};
