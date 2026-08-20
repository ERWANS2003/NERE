<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->string('slug')->unique()->after('nom');
            $table->boolean('met_en_pause_sla')->default(false)->after('est_final');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('type', ['incident', 'demande'])->default('incident')->after('description');
            $table->text('solution')->nullable()->after('type');
            $table->string('motif_attente')->nullable()->after('solution');
            $table->timestamp('date_mise_en_attente')->nullable()->after('date_assignation');
            $table->unsignedInteger('sla_temps_pause_secondes')->default(0)->after('sla_depasse');
        });

        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->enum('type', ['incident', 'demande', 'les_deux'])->default('les_deux')->after('nom');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['type', 'solution', 'motif_attente', 'date_mise_en_attente', 'sla_temps_pause_secondes']);
        });

        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->dropColumn(['slug', 'met_en_pause_sla']);
        });
    }
};
