<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compte désactivé par un administrateur de l'intranet : l'AD ne le réactive plus.
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('desactive_localement')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('desactive_localement');
        });
    }
};
