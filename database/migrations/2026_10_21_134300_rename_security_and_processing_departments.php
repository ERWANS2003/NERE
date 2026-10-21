<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('departments')->where('code', 'SEC')->update(['name' => 'Département Sûreté']);
        DB::table('departments')->where('code', 'PROC')->update(['name' => 'Département Process']);
    }

    public function down(): void
    {
        DB::table('departments')->where('code', 'SEC')->update(['name' => 'Département Sécurité']);
        DB::table('departments')->where('code', 'PROC')->update(['name' => 'Département Processing']);
    }
};
