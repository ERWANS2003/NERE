<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use App\Models\TicketCategory;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Seed reference data (statuses, priorities, categories)
     * Ces données sont déjà créées par MiningCompanyDataSeeder
     * Ce seeder assure qu'elles existent même si appelé seul
     */
    public function run(): void
    {
        // Vérifier si les données existent déjà
        if (TicketStatus::count() > 0) {
            $this->command->info('Reference data already exists, skipping...');
            return;
        }

        $this->command->info('Reference data will be seeded by MiningCompanyDataSeeder');
    }
}
