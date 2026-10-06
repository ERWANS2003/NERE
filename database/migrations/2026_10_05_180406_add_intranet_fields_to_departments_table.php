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
        Schema::table('departments', function (Blueprint $table) {
            // Champs pour l'intranet
            $table->string('email')->nullable()->after('description');
            $table->string('phone')->nullable()->after('email');
            $table->string('location')->nullable()->after('phone');
            $table->decimal('budget', 15, 2)->nullable()->after('location');
            $table->boolean('allow_ticket_creation')->default(true)->after('is_active');
            
            // Relations hiérarchiques
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete()->after('id');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete()->after('parent_id');
            
            // Index pour améliorer les performances
            $table->index('parent_id');
            $table->index('manager_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['is_active']);
            
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['manager_id']);
            
            $table->dropColumn([
                'email',
                'phone', 
                'location',
                'budget',
                'allow_ticket_creation',
                'parent_id',
                'manager_id'
            ]);
        });
    }
};