<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes de liaison Active Directory (LdapRecord) sur `users`.
 *
 * - `guid`   : objectGUID AD, identifiant stable qui survit à un renommage du
 *              compte ; sert de clé de rapprochement AD ↔ compte local.
 * - `domain` : nom de la connexion LDAP d'origine (ici « default »).
 *
 * Idempotent : ne crée les colonnes que si elles manquent (base déjà migrée).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'guid')) {
                $table->string('guid')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('users', 'domain')) {
                $table->string('domain')->nullable()->after('guid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['guid', 'domain'] as $colonne) {
                if (Schema::hasColumn('users', $colonne)) {
                    $table->dropColumn($colonne);
                }
            }
        });
    }
};
