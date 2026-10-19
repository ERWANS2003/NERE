<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CATEGORIES = [
        'Demande de matériel' => 'hardware',
        'Création de compte' => 'access',
        'Téléphonie et réseau' => 'network',
        'Sauvegarde et restauration' => 'backup',
        'Sécurité informatique' => 'security',
    ];

    public function up(): void
    {
        $departmentId = DB::table('departments')->where('code', 'IT')->value('id');

        if ($departmentId === null) {
            return;
        }

        foreach (self::CATEGORIES as $name => $category) {
            DB::table('department_services')
                ->where('department_id', $departmentId)
                ->where('name', $name)
                ->update([
                    'link_type' => 'page',
                    'link_url' => '/intranet/tickets/create?category='.$category,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $departmentId = DB::table('departments')->where('code', 'IT')->value('id');

        if ($departmentId === null) {
            return;
        }

        DB::table('department_services')
            ->where('department_id', $departmentId)
            ->whereIn('name', array_keys(self::CATEGORIES))
            ->update([
                'link_type' => 'form',
                'link_url' => null,
                'updated_at' => now(),
            ]);
    }
};
