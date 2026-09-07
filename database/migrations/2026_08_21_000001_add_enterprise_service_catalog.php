<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $services = [
            'Production' => ['code' => 'PRO', 'team' => 'Équipe Production', 'categories' => ['Incident de production', 'Équipement de production']],
            'Géologie' => ['code' => 'GEO', 'team' => 'Équipe Géologie', 'categories' => ['Échantillonnage', 'Données géologiques']],
            'RH' => ['code' => 'RH', 'team' => 'Équipe RH', 'categories' => ['Congé', 'Attestation', 'Recrutement', 'Formation', 'Contrat', 'Paie']],
            'Finance' => ['code' => 'FIN', 'team' => 'Équipe Finance', 'categories' => ['Demande de paiement', 'Budget', 'Facturation']],
            'HSE' => ['code' => 'HSE', 'team' => 'Équipe HSE', 'categories' => ['Accident', 'Presqu\'accident', 'Inspection', 'Observation', 'Demande d\'EPI', 'Incendie', 'Environnement']],
            'Achats' => ['code' => 'ACH', 'team' => 'Équipe Achats', 'categories' => ['Demande d\'achat', 'Commande fournisseur']],
            'Logistique' => ['code' => 'LOG', 'team' => 'Équipe Logistique', 'categories' => ['Véhicule', 'Transport', 'Carburant']],
        ];

        foreach ($services as $nom => $service) {
            DB::table('departements')->insertOrIgnore([
                'nom' => $nom,
                'code' => $service['code'],
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $departementId = DB::table('departements')->where('nom', $nom)->value('id');
            DB::table('teams')->insertOrIgnore([
                'nom' => $service['team'],
                'departement_id' => $departementId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $teamId = DB::table('teams')->where('nom', $service['team'])->where('departement_id', $departementId)->value('id');
            foreach ($service['categories'] as $categorie) {
                DB::table('ticket_categories')->insertOrIgnore([
                    'nom' => $categorie,
                    'team_id' => $teamId,
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['Production', 'Géologie', 'RH', 'Finance', 'HSE', 'Achats', 'Logistique'] as $nom) {
            $departementId = DB::table('departements')->where('nom', $nom)->value('id');
            $teamIds = DB::table('teams')->where('departement_id', $departementId)->pluck('id');
            DB::table('ticket_categories')->whereIn('team_id', $teamIds)->delete();
            DB::table('team_user')->whereIn('team_id', $teamIds)->delete();
            DB::table('teams')->whereIn('id', $teamIds)->delete();
            DB::table('departements')->where('id', $departementId)->delete();
        }
    }
};
