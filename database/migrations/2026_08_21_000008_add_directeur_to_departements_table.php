<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            // Directeur du département (utilisateur avec le rôle directeur_departement)
            $table->foreignId('directeur_id')->nullable()->after('actif')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departements', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'directeur_id');
            $table->dropColumn('directeur_id');
        });
    }
};
