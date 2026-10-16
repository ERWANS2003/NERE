<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('departments')
            ->where('code', 'ADMIN')
            ->where('name', 'Administration de la mine')
            ->update(['name' => 'Administration']);
    }

    public function down(): void
    {
        DB::table('departments')
            ->where('code', 'ADMIN')
            ->where('name', 'Administration')
            ->update(['name' => 'Administration de la mine']);
    }
};
