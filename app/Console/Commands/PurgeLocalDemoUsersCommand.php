<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Supprime les comptes locaux de démonstration après le passage à Active
 * Directory. Les utilisateurs AD sont identifiés par `guid` et le compte
 * super-administrateur local est toujours conservé.
 *
 * La commande est volontairement en simulation par défaut : il faut passer
 * --force pour confirmer une suppression potentiellement en cascade.
 */
class PurgeLocalDemoUsersCommand extends Command
{
    protected $signature = 'users:purge-local-demo
                            {--dry-run : Affiche les comptes ciblés sans supprimer}
                            {--force : Confirme la suppression des comptes et de leurs dépendances}';

    protected $description = 'Supprime les comptes locaux de démonstration en conservant AD et super-admin';

    public function handle(): int
    {
        $users = User::query()
            ->whereNull('guid')
            ->where('is_super_admin', false)
            ->orderBy('id')
            ->get(['id', 'name', 'email']);

        if ($users->isEmpty()) {
            $this->info('Aucun compte local de démonstration à supprimer.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Nom', 'Email'],
            $users->map(fn (User $user) => [$user->id, $user->name, $user->email])->all(),
        );
        $this->warn("{$users->count()} compte(s) local(aux) ciblé(s).");

        if ($this->option('dry-run')) {
            $this->info('Simulation terminée : aucune donnée n\'a été modifiée.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('Suppression bloquée. Relancez avec --force après vérification.');

            return self::FAILURE;
        }

        $ids = $users->modelKeys();
        DB::transaction(fn () => User::whereKey($ids)->delete());

        $this->info("{$users->count()} compte(s) supprimé(s). Les cascades prévues par PostgreSQL ont été appliquées.");
        $this->line('Utilisateurs AD conservés : '.User::whereNotNull('guid')->count());
        $this->line('Super-administrateurs conservés : '.User::where('is_super_admin', true)->count());

        return self::SUCCESS;
    }
}
