<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Ticket::with(['categorie', 'priorite', 'statut', 'site', 'assigned_to'])
            ->get()
            ->map(function ($ticket) {
                return [
                    'Référence' => $ticket->reference,
                    'Titre' => $ticket->titre,
                    'Catégorie' => $ticket->categorie?->nom ?? '—',
                    'Priorité' => $ticket->priorite?->nom ?? '—',
                    'Statut' => $ticket->statut?->nom ?? '—',
                    'Site' => $ticket->site?->nom ?? '—',
                    'Assigné à' => $ticket->assigned_to ? $ticket->assignedTo?->name : '—',
                    'Créé le' => $ticket->created_at?->format('d/m/Y H:i') ?? '—',
                    'Résolu le' => $ticket->date_resolution?->format('d/m/Y H:i') ?? '—',
                    'SLA Dépassé' => $ticket->sla_depasse ? 'Oui' : 'Non',
                    'Satisfaction' => $ticket->satisfaction_note ?? '—',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Référence',
            'Titre',
            'Catégorie',
            'Priorité',
            'Statut',
            'Site',
            'Assigné à',
            'Créé le',
            'Résolu le',
            'SLA Dépassé',
            'Satisfaction',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
