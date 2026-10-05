<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot des droits par departement.
     *
     * Une ligne = un role pour un utilisateur dans un departement donne.
     * L'unicite (user_id, department_id) evite les doublons : le directeur
     * herite des droits technicien dans son departement, donc deux lignes pour
     * le meme couple n'ont aucun sens.
     *
     * Les domaines sont exprimes en varchar + contraintes CHECK plutot qu'en type
     * ENUM natif PostgreSQL : ajouter une valeur a un enum natif est painful a
     * migrer, alors qu'un CHECK se modifie en une ligne. Le type PHP
     * correspondant (App\Enums\DepartmentRole) reste la source de verite cote
     * application.
     *
     * La table est creee en SQL brut pour une seule raison : SQLite ne accepte
     * pas `ALTER TABLE ... ADD CONSTRAINT`, il exige que les CHECK soient
     * ecrits dans le CREATE TABLE. Passer par le constructeur de schema puis
     * ajouter les contraintes apres coup ne fonctionnerait donc qu'en
     * production, et les tests — qui tournent sur SQLite — ne verifies jamais
     * qu'une affectation invalide est bien refusee. Ici les deux moteurs
     * recoivent exactement la meme definition.
     */
    public function up(): void
    {
        // Seule divergence entre PostgreSQL et SQLite : le type de la colonne primaire
        // autoincrementee. SQLite est exigeant sur la casse — `AUTOINCREMENT`
        // n'est accepte qu'apres un `INTEGER PRIMARY KEY` en majuscules — et la
        // colonne doit imperativement etre nommee, sinon le parseur echoue sur
        // une definition de colonne sans identifiant.
        $clePrimaire = DB::getDriverName() === 'pgsql'
            ? 'id bigserial PRIMARY KEY'
            : 'id INTEGER PRIMARY KEY AUTOINCREMENT';

        DB::statement(<<<SQL
            CREATE TABLE department_user (
                $clePrimaire,
                user_id bigint NOT NULL,
                department_id bigint NOT NULL,
                role varchar(20) NOT NULL,
                tech_level smallint NULL,
                created_at timestamp NULL,
                updated_at timestamp NULL,

                CONSTRAINT department_user_user_id_foreign
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT department_user_department_id_foreign
                    FOREIGN KEY (department_id) REFERENCES departments (id) ON DELETE CASCADE,
                CONSTRAINT department_user_unique
                    UNIQUE (user_id, department_id),

                CONSTRAINT department_user_role_check
                    CHECK (role IN ('user', 'technician', 'director')),
                CONSTRAINT department_user_tech_level_check
                    CHECK (tech_level IS NULL OR tech_level BETWEEN 1 AND 3),

                -- Un role sans niveau, ou un niveau sans role technicien, est une
                -- erreur de saisie : on la bloque au niveau de la base plutot qu'en
                -- comptant sur les formulaires d'affectation.
                CONSTRAINT department_user_level_required_check
                    CHECK (
                        (role = 'technician' AND tech_level IS NOT NULL)
                        OR (role <> 'technician' AND tech_level IS NULL)
                    )
            )
        SQL);

        // Index hors CREATE TABLE : lisibles par le constructeur de schema et
        // toujours crees apres la table, quel que soit le moteur.
        Schema::table('department_user', function (Blueprint $table) {
            $table->index(['department_id', 'role'], 'department_user_department_role_index');
            $table->index(['department_id', 'tech_level'], 'department_user_department_level_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
