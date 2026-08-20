<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('nom_logiciel'); // Ex: Microsoft 365, AutoCAD, Antivirus...
            $table->string('cle_licence')->nullable();
            $table->string('fournisseur')->nullable();
            $table->unsignedInteger('nombre_postes')->default(1);
            $table->date('date_achat')->nullable();
            $table->date('date_expiration')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
