<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fiche saisie à la main : marque, n° de série, garantie, et champs propres au type.
        Schema::table('monitor_devices', fn (Blueprint $t) => $t->json('details')->nullable());
    }

    public function down(): void
    {
        Schema::table('monitor_devices', fn (Blueprint $t) => $t->dropColumn('details'));
    }
};
