<?php

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @param  Builder<Ticket>  $query  Already scoped by the caller, normally
     *                                 Ticket::visibleA($user). The export used
     *                                 to read Ticket::get() with no filter at
     *                                 all, so an export contained every
     *                                 department's tickets.
     */
    public function __construct(protected ?Builder $query = null) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ($this->query ?? Ticket::query())
            ->with(['categorie', 'priorite', 'statut', 'site', 'technicien', 'demandeur'])
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket) => [
                'Référence' => $ticket->reference,
                'Titre' => $ticket->titre,
                'Catégorie' => $ticket->categorie?->nom ?? '—',
                'Priorité' => $ticket->priorite?->nom ?? '—',
                'Statut' => $ticket->statut?->nom ?? '—',
                'Site' => $ticket->site?->nom ?? '—',
                'Assigné à' => $ticket->technicien?->name ?? '—',
                'Demandeur' => $ticket->demandeur?->name ?? '—',
                'Créé le' => $ticket->created_at?->format('d/m/Y H:i') ?? '—',
                'Résolu le' => $ticket->date_resolution?->format('d/m/Y H:i') ?? '—',
                'SLA Dépassé' => $ticket->sla_depasse ? 'Oui' : 'Non',
                'Satisfaction' => $ticket->satisfaction_note ?? '—',
            ]);
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
            'Demandeur',
            'Créé le',
            'Résolu le',
            'SLA Dépassé',
            'Satisfaction',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'C83530']],
            ],
        ];
    }
}
