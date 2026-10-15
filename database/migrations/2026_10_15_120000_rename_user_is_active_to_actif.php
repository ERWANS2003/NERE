<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'is_active') && ! Schema::hasColumn('users', 'actif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('is_active', 'actif');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'actif') && ! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('actif', 'is_active');
            });
        }
    }
};
