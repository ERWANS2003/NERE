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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('manager_id')->nullable()->after('departement_id')->constrained('users')->onDelete('set null');
            $table->string('position_title')->nullable()->after('poste');
            $table->integer('org_level')->default(1)->after('position_title'); // Niveau hiérarchique
            $table->date('hire_date')->nullable()->after('org_level');
            $table->date('birth_date')->nullable()->after('hire_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['manager_id', 'position_title', 'org_level', 'hire_date', 'birth_date']);
        });
    }
};
