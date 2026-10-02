<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['slug' => Role::SLUG_ADMIN],
            ['nom' => 'Administrateur']
        );

        $admin = User::firstOrNew(['email' => 'admin@nere-mining.bf']);

        if (! $admin->exists) {
            $admin->fill([
                'name' => 'Administrateur',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
                'actif' => true,
                'role_id' => $adminRole->id,
            ])->save();
        } elseif (! $admin->role_id) {
            // Ne réécrit que le rôle manquant : un `updateOrCreate` ici
            // remettait le mot de passe à `admin123` et cassait l'activation
            // d'un compte déjà personnalisé à chaque `db:seed`.
            $admin->forceFill(['role_id' => $adminRole->id])->save();
        }

        $this->command->info(sprintf(
            'Admin %s : %s',
            $admin->exists ? 'vérifié' : 'créé',
            $admin->email
        ));
    }
}
