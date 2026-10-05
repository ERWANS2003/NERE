<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('intranet_submissions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            // chemin dans le disque privé : intranet/submissions/{id}/...
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);   // octets
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_attachments');
    }
};
