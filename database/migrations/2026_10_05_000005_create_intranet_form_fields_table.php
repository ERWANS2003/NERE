<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('intranet_forms')->cascadeOnDelete();
            $table->string('label');
            // text|textarea|number|date|select|multiselect|checkbox|file|user|table
            $table->string('type', 20)->default('text');
            // options : pour select/multiselect : [{label, value}]
            $table->jsonb('options')->nullable();
            // rules : ex. {"max":255,"min":0,"mimes":"pdf,jpg"}
            $table->jsonb('rules')->nullable();
            $table->boolean('required')->default(false);
            $table->string('help_text')->nullable();
            // visibilité conditionnelle : {"field_id":42,"operator":"=","value":"oui"}
            $table->jsonb('condition')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_form_fields');
    }
};
