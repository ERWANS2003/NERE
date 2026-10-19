<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'poste')) {
                $table->string('poste')->nullable();
            }

            if (! Schema::hasColumn('users', 'telephone')) {
                $table->string('telephone')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_filter(
                ['poste', 'telephone'],
                fn (string $column): bool => Schema::hasColumn('users', $column),
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
