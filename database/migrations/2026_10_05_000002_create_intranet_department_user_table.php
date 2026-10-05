<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('intranet_departments')->cascadeOnDelete();
            // role : user | technician | director
            $table->string('role', 20)->default('user');
            // niveau technicien 1/2/3, null si non technicien
            $table->unsignedTinyInteger('tech_level')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_department_user');
    }
};
