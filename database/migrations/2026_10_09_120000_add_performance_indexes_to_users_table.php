<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance sur `users`.
 *
 * La table `users` est la plus sollicitée de l'intranet (annuaire, organigramme,
 * tableau de bord, approbation des congés) mais ne portait que les index
 * email / matricule / clé primaire. Chaque requête filtrait et triait sur des
 * colonnes non indexées :
 *   - annuaire : WHERE actif = true ORDER BY name
 *   - organigramme : WHERE actif = true AND org_level = 0
 *   - congés : WHERE manager_id = ?  (une FK, non indexée automatiquement par PostgreSQL)
 *
 * Chaque bloc est conditionnel : si l'index existe déjà (base partiellement
 * migrée) ou si une colonne manque, on n'échoue pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if ($this->missesIndex('users_actif_name_index') && Schema::hasColumn('users', 'actif') && Schema::hasColumn('users', 'name')) {
                $table->index(['actif', 'name'], 'users_actif_name_index');
            }

            if ($this->missesIndex('users_org_level_index') && Schema::hasColumn('users', 'org_level')) {
                $table->index('org_level', 'users_org_level_index');
            }

            if ($this->missesIndex('users_manager_id_index') && Schema::hasColumn('users', 'manager_id')) {
                $table->index('manager_id', 'users_manager_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['users_actif_name_index', 'users_org_level_index', 'users_manager_id_index'] as $name) {
                if (! $this->missesIndex($name)) {
                    $table->dropIndex($name);
                }
            }
        });
    }

    /**
     * Vrai si l'index n'existe pas encore. Sur SQLite (tests), on laisse
     * toujours créer : le dédoublonnage par nom n'y est pas critique.
     */
    private function missesIndex(string $name): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return true;
        }

        $rows = Schema::getConnection()->select(
            'select 1 from pg_indexes where tablename = ? and indexname = ?',
            ['users', $name],
        );

        return $rows === [];
    }
};
