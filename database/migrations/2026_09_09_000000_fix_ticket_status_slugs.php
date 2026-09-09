<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Map old English slugs to new French slugs
        $slugMap = [
            'new' => 'nouveau',
            'open' => 'assigne',
            'assigned' => 'assigne',
            'in_progress' => 'en_cours',
            'waiting' => 'en_attente',
            'resolved' => 'resolu',
            'closed' => 'clos',
            'rejected' => 'annule',
        ];

        foreach ($slugMap as $oldSlug => $newSlug) {
            DB::table('ticket_statuses')
                ->where('slug', $oldSlug)
                ->update(['slug' => $newSlug]);
        }

        // Update status names to match French convention
        $nameMap = [
            'Ouvert' => 'Assigné',
            'Rejeté' => 'Annulé',
        ];

        foreach ($nameMap as $oldName => $newName) {
            DB::table('ticket_statuses')
                ->where('nom', $oldName)
                ->update(['nom' => $newName]);
        }
    }

    public function down(): void
    {
        // Reverse the slug changes
        $slugMap = [
            'nouveau' => 'new',
            'assigne' => 'assigned',
            'en_cours' => 'in_progress',
            'en_attente' => 'waiting',
            'resolu' => 'resolved',
            'clos' => 'closed',
            'annule' => 'rejected',
        ];

        foreach ($slugMap as $newSlug => $oldSlug) {
            // Only revert if the mapping was 1-to-1 (not 'open' which also maps to 'assigne')
            if ($oldSlug !== 'assigned') {
                DB::table('ticket_statuses')
                    ->where('slug', $newSlug)
                    ->where('nom', '!=', 'Assigné') // Don't touch the actual 'Assigné' status
                    ->update(['slug' => $oldSlug]);
            }
        }
    }
};
