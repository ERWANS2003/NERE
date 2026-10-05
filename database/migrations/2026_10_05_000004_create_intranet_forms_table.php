<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('intranet_services')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40)->unique();   // ex. IT-INCIDENT-V2
            $table->unsignedSmallInteger('version')->default(1);
            // draft | published | archived
            $table->string('status', 20)->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_forms');
    }
};
