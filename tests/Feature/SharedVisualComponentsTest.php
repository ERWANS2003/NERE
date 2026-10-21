<?php

use Illuminate\Support\Facades\Blade;

it('renders a shared page header with a description and action slot', function () {
    $html = Blade::render(<<<'BLADE'
        <x-intranet.page-header title="Mes demandes" description="Suivez leur avancement">
            <x-slot:actions>
                <x-button href="/demandes/nouvelle">Nouvelle demande</x-button>
            </x-slot:actions>
        </x-intranet.page-header>
        BLADE);
    $submitButton = Blade::render('<x-button type="submit" variant="secondary">Filtrer</x-button>');

    expect($html)
        ->toContain('<h1 class="text-3xl font-bold text-graphite-900">Mes demandes</h1>')
        ->toContain('Suivez leur avancement')
        ->toContain('href="/demandes/nouvelle"')
        ->toContain('Nouvelle demande');
    expect($submitButton)
        ->toContain('type="submit"')
        ->toContain('bouton-secondaire')
        ->toContain('Filtrer');
});

it('renders filter forms and filter groups with accessible labels', function () {
    $form = Blade::render(<<<'BLADE'
        <x-intranet.filters as-form action="/tickets" label="Filtrer les tickets" data-live-search>
            <input name="status">
        </x-intranet.filters>
        BLADE);
    $group = Blade::render(<<<'BLADE'
        <x-intranet.filters label="Filtrer par statut">
            <x-intranet.filter-link href="/demandes?status=pending" :active="true">En attente</x-intranet.filter-link>
        </x-intranet.filters>
        BLADE);

    expect($form)
        ->toContain('<form')
        ->toContain('method="GET"')
        ->toContain('action="/tickets"')
        ->toContain('aria-label="Filtrer les tickets"')
        ->toContain('data-live-search');
    expect($group)
        ->toContain('role="group"')
        ->toContain('aria-label="Filtrer par statut"')
        ->toContain('aria-current="page"')
        ->toContain('intranet-filter-link-active');
});

it('renders cards and responsive table containers with shared classes', function () {
    $card = Blade::render('<x-card as="article" flat>Guide de dépannage</x-card>');
    $table = Blade::render('<x-intranet.table><table><tbody><tr><td>Demande</td></tr></tbody></table></x-intranet.table>');

    expect($card)
        ->toContain('<article')
        ->toContain('carte-plate')
        ->toContain('Guide de dépannage');
    expect($table)
        ->toContain('intranet-table-container')
        ->toContain('<td>Demande</td>');
});

it('renders semantic status badges for intranet workflows', function () {
    $ticketStatus = Blade::render('<x-intranet.status-badge status="in_progress" />');
    $leaveStatus = Blade::render('<x-intranet.status-badge status="approved" context="leave" />');

    expect($ticketStatus)
        ->toContain('nere-badge-warning')
        ->toContain('En cours');
    expect($leaveStatus)
        ->toContain('nere-badge-success')
        ->toContain('Approuvé');
});
