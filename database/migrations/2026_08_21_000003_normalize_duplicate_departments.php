<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('departements')
            ->select('nom', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->groupBy('nom')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $duplicateIds = DB::table('departements')
                ->where('nom', $duplicate->nom)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('teams')->whereIn('departement_id', $duplicateIds)->update(['departement_id' => $duplicate->keep_id]);
            DB::table('users')->whereIn('departement_id', $duplicateIds)->update(['departement_id' => $duplicate->keep_id]);
            DB::table('tickets')->whereIn('departement_id', $duplicateIds)->update(['departement_id' => $duplicate->keep_id]);
            DB::table('departements')->whereIn('id', $duplicateIds)->delete();
        }
    }

    public function down(): void
    {
        // La consolidation des services ne peut pas être inversée sans recréer des doublons.
    }
};
