<?php

use App\Support\RoleProfileCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        RoleProfileCatalog::seed();
    }

    public function down(): void
    {
        // Profiles may have been assigned or customized after this migration.
    }
};
