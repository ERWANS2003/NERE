<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();           // ex. IT, RH, HSE
            $table->string('tag', 50);                       // ex. "TECHNOLOGIES"
            $table->string('name');                          // ex. "Département IT"
            $table->text('description')->nullable();
            $table->string('icon', 60)->default('briefcase'); // héroïcon
            $table->string('color', 20)->default('#e0a52f'); // couleur accent
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_departments');
    }
};
