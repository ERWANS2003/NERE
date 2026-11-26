<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identifiants SNMPv3 chiffrés avec APP_KEY : {"user", "auth", "auth_password", "priv", "priv_password"}.
        foreach (['monitor_groups', 'monitor_devices'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->text('snmp_v3')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['monitor_groups', 'monitor_devices'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('snmp_v3'));
        }
    }
};
