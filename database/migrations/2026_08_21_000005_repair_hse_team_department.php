<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hseId = DB::table('departements')->where('nom', 'HSE')->value('id');
        if ($hseId) {
            DB::table('teams')->where('nom', 'Équipe HSE')->update(['departement_id' => $hseId]);
        }
    }

    public function down(): void
    {
        // Le rattachement réparé doit rester en place.
    }
};
