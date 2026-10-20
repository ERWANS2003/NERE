<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['permission_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
    }
};
