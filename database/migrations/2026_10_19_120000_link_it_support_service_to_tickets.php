<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departmentId = DB::table('departments')->where('code', 'IT')->value('id');

        if ($departmentId === null) {
            return;
        }

        $existingSupport = DB::table('department_services')
            ->where('department_id', $departmentId)
            ->where('name', 'Support IT')
            ->first();

        $oldSupport = DB::table('department_services')
            ->where('department_id', $departmentId)
            ->where('name', 'Support technique')
            ->first();

        if ($existingSupport && $oldSupport) {
            DB::table('department_services')->where('id', $oldSupport->id)->delete();
        } elseif ($oldSupport) {
            DB::table('department_services')
                ->where('id', $oldSupport->id)
                ->update(['name' => 'Support IT']);
        }

        DB::table('department_services')
            ->where('department_id', $departmentId)
            ->where('name', 'Support IT')
            ->update([
                'description' => 'Signaler un incident informatique et suivre vos demandes d’assistance : matériel, logiciels, réseau, imprimantes et accès.',
                'link_type' => 'page',
                'link_url' => '/intranet/tickets',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $departmentId = DB::table('departments')->where('code', 'IT')->value('id');

        if ($departmentId === null) {
            return;
        }

        DB::table('department_services')
            ->where('department_id', $departmentId)
            ->where('name', 'Support IT')
            ->update([
                'name' => 'Support technique',
                'link_type' => 'form',
                'link_url' => null,
                'updated_at' => now(),
            ]);
    }
};
