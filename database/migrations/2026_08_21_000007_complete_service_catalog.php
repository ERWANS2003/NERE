<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalogue = [
            'IT' => ['Poste de travail', 'Email', 'VPN', 'Imprimante', 'SAP', 'Office', 'Téléphone'],
            'Maintenance' => ['Engin', 'Pompe', 'Convoyeur', 'Générateur', 'Climatisation', 'Électricité'],
            'Finance' => ['Avance de fonds / note de frais', 'Réclamation de facturation'],
            'Logistique' => ['Transport de personnel', 'Approvisionnement / pièces détachées'],
            'Production' => ['Anomalie de process', 'Arrêt de production', 'Support supervision'],
            'Géologie' => ['Support logiciel SIG / modélisation', 'Matériel de terrain', 'Accès aux données géologiques'],
        ];

        foreach ($catalogue as $service => $categories) {
            $departementId = DB::table('departements')->where('nom', $service)->value('id');
            if (! $departementId) {
                continue;
            }

            $teamId = DB::table('teams')->where('departement_id', $departementId)->orderBy('id')->value('id');
            if (! $teamId) {
                continue;
            }

            foreach ($categories as $nom) {
                $exists = DB::table('ticket_categories')->where('nom', $nom)->exists();
                if (! $exists) {
                    DB::table('ticket_categories')->insert([
                        'nom' => $nom,
                        'team_id' => $teamId,
                        'actif' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $categories = ['Poste de travail', 'Email', 'VPN', 'Imprimante', 'SAP', 'Office', 'Téléphone', 'Engin', 'Pompe', 'Convoyeur', 'Générateur', 'Climatisation', 'Électricité', 'Avance de fonds / note de frais', 'Réclamation de facturation', 'Transport de personnel', 'Approvisionnement / pièces détachées', 'Anomalie de process', 'Arrêt de production', 'Support supervision', 'Support logiciel SIG / modélisation', 'Matériel de terrain', 'Accès aux données géologiques'];
        DB::table('ticket_categories')->whereIn('nom', $categories)->delete();
    }
};
