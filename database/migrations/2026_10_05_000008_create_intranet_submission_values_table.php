<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('intranet_submissions')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('intranet_form_fields')->cascadeOnDelete();
            // valeur sérialisée (string, JSON pour multiselect/table)
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_submission_values');
    }
};
