<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'meal_location_id')) {
                $table->foreignId('meal_location_id')
                    ->nullable()
                    ->after('domain')
                    ->constrained('meal_locations')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'meal_location_id')) {
                $table->dropConstrainedForeignId('meal_location_id');
            }
        });
    }
};
