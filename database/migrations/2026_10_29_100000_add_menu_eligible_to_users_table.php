<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Employés autorisés à choisir leurs plats dans le menu du personnel.
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('menu_eligible')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('menu_eligible');
        });
    }
};
