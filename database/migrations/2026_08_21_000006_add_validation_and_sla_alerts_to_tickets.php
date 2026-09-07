<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('validation_statut', ['non_requise', 'en_attente', 'valide', 'refuse'])->default('non_requise')->after('satisfaction_commentaire');
            $table->foreignId('valide_par')->nullable()->after('validation_statut')->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable()->after('valide_par');
            $table->boolean('sla_alerte_75_envoyee')->default(false)->after('sla_depasse');
            $table->boolean('sla_alerte_100_envoyee')->default(false)->after('sla_alerte_75_envoyee');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['valide_par']);
            $table->dropColumn(['validation_statut', 'valide_par', 'date_validation', 'sla_alerte_75_envoyee', 'sla_alerte_100_envoyee']);
        });
    }
};
