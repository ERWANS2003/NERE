<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ces colonnes sont désormais déclarées directement dans la migration
        // de création de la table `users`. On ne les ajoute ici que si elles
        // manquent, pour rester compatible avec une base déjà migrée (où cette
        // migration a tourné avant) comme avec une base neuve (tests SQLite),
        // sans provoquer de « duplicate column ».
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'failed_attempts')) {
                $table->integer('failed_attempts')->default(0)->nullable();
            }

            if (! Schema::hasColumn('users', 'locked_until')) {
                $table->dateTime('locked_until')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // On ne retire rien : les colonnes appartiennent maintenant à la
        // migration de création de `users`, c'est elle qui les supprime.
    }
};
