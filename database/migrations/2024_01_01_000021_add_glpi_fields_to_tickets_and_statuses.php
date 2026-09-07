<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticket_statuses', 'slug')) {
            Schema::table('ticket_statuses', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('nom');
            });
        }

        if (! Schema::hasColumn('ticket_statuses', 'met_en_pause_sla')) {
            Schema::table('ticket_statuses', function (Blueprint $table) {
                $table->boolean('met_en_pause_sla')->default(false)->after('est_final');
            });
        }

        $slugs = [
            'Nouveau' => 'nouveau',
            'Assigné' => 'assigne',
            'En cours' => 'en_cours',
            'En attente' => 'en_attente',
            'Résolu' => 'resolu',
            'Fermé' => 'clos',
            'Clos' => 'clos',
            'Annulé' => 'annule',
        ];

        foreach (DB::table('ticket_statuses')->select(['id', 'nom'])->get() as $status) {
            DB::table('ticket_statuses')->where('id', $status->id)->update([
                'slug' => $slugs[$status->nom] ?? Str::of($status->nom)->ascii()->lower()->replace(' ', '_')->value(),
            ]);
        }

        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'type')) $table->enum('type', ['incident', 'demande'])->default('incident')->after('description');
            if (! Schema::hasColumn('tickets', 'solution')) $table->text('solution')->nullable()->after('type');
            if (! Schema::hasColumn('tickets', 'motif_attente')) $table->string('motif_attente')->nullable()->after('solution');
            if (! Schema::hasColumn('tickets', 'date_mise_en_attente')) $table->timestamp('date_mise_en_attente')->nullable()->after('date_assignation');
            if (! Schema::hasColumn('tickets', 'sla_temps_pause_secondes')) $table->unsignedInteger('sla_temps_pause_secondes')->default(0)->after('sla_depasse');
        });

        if (! Schema::hasColumn('ticket_categories', 'type')) {
            Schema::table('ticket_categories', function (Blueprint $table) {
                $table->enum('type', ['incident', 'demande', 'les_deux'])->default('les_deux')->after('nom');
            });
        }
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
