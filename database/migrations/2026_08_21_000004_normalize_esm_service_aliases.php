<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hseId = DB::table('departements')->where('nom', 'HSE')->value('id');
        $longHseId = DB::table('departements')->where('nom', 'Hygiène, Sécurité et Environnement')->value('id');

        if (! $hseId && $longHseId) {
            DB::table('departements')->where('id', $longHseId)->update(['nom' => 'HSE', 'code' => 'HSE']);
            $hseId = $longHseId;
        } elseif ($hseId && $longHseId && $hseId !== $longHseId) {
            $this->mergeDepartment($longHseId, $hseId);
        }

        $securityId = DB::table('departements')->where('nom', 'Sécurité')->value('id');
        if ($securityId && $hseId && $securityId !== $hseId) {
            $this->mergeDepartment($securityId, $hseId);
        }
    }

    private function mergeDepartment(int $fromId, int $toId): void
    {
        DB::table('teams')->where('departement_id', $fromId)->update(['departement_id' => $toId]);
        DB::table('users')->where('departement_id', $fromId)->update(['departement_id' => $toId]);
        DB::table('tickets')->where('departement_id', $fromId)->update(['departement_id' => $toId]);
        DB::table('departements')->where('id', $fromId)->delete();
    }

    public function down(): void
    {
        // Les alias historiques ne sont pas recréés lors d'un rollback.
    }
};
