@extends('admin.layout')

@section('title', 'Menus du personnel')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h3 class="text-lg font-semibold text-gray-900">Menus hebdomadaires</h3>
        <p class="text-sm text-gray-600">Gérez les menus par localisation de restauration.</p>
    </div>
    <a href="{{ route('admin.menus.create') }}" class="rounded bg-nere-600 px-4 py-2 text-white hover:bg-nere-700">
        <i class="fas fa-plus mr-2"></i>Créer un menu
    </a>
</div>

<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Semaine</th>
                    <th class="px-4 py-3 font-medium">Localisation</th>
                    <th class="px-4 py-3 font-medium">Jours</th>
                    <th class="px-4 py-3 font-medium">État</th>
                    <th class="px-4 py-3 text-right font-medium">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($menus as $menu)
                    <tr>
                        <td class="px-4 py-3">{{ $menu->week_start->format('d/m/Y') }} – {{ $menu->week_end->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $menu->location->name }}</td>
                        <td class="px-4 py-3">{{ $menu->days_count }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusLabel = ['draft' => 'Brouillon', 'published' => 'Publié', 'closed' => 'Clôturé', 'archived' => 'Archivé'][$menu->status] ?? $menu->status;
                            @endphp
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.menus.show', $menu) }}" class="font-medium text-nere-600 hover:text-nere-700">Détails</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Aucun menu n’a encore été créé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-gray-200 px-4 py-3">{{ $menus->links() }}</div>
</div>
@endsection
