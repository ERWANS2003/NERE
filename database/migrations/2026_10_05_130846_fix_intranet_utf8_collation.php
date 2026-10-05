<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Note: PostgreSQL gère UTF-8 par défaut. Cette migration est optionnelle.
        // Les accents s'affichent mal parce que:
        // 1. Les headers HTTP n'avaient pas charset=utf-8 (FIXÉ par le middleware)
        // 2. La base de données stocke correctement les données (vérifiée)
        // 
        // PostgreSQL n'a pas besoin de migration de collation.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
