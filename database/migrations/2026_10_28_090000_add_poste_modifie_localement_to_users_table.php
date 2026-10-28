<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Poste saisi dans l'intranet : la synchronisation AD ne l'écrase plus.
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('poste_modifie_localement')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('poste_modifie_localement');
        });
    }
};
