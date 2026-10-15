@extends('admin.layout')

@section('title', 'Détails du menu')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.menus.index') }}" class="mb-3 inline-block text-sm text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i>Retour aux menus
        </a>
        <h3 class="text-xl font-semibold text-gray-900">{{ $menu->location->name }}</h3>
        <p class="text-sm text-gray-600">Semaine du {{ $menu->week_start->format('d/m/Y') }} au {{ $menu->week_end->format('d/m/Y') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($menu->status === \App\Models\WeeklyMenu::DRAFT)
            <form method="POST" action="{{ route('admin.menus.publish', $menu) }}">
                @csrf
                <button class="rounded bg-green-700 px-4 py-2 text-white hover:bg-green-800">Publier</button>
            </form>
        @elseif($menu->status === \App\Models\WeeklyMenu::PUBLISHED)
            <form method="POST" action="{{ route('admin.menus.close', $menu) }}">
                @csrf
                <button class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-700">Clôturer</button>
            </form>
        @endif
        <a href="{{ route('admin.menus.export', $menu) }}" class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">Exporter Excel</a>
        @if($menu->status !== \App\Models\WeeklyMenu::PUBLISHED)
            <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" onsubmit="return confirm('Supprimer définitivement ce menu ?')">
                @csrf
                @method('DELETE')
                <button class="rounded border border-red-300 px-4 py-2 text-red-700 hover:bg-red-50">Supprimer</button>
            </form>
        @endif
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Personnel concerné</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $eligibleCount }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Réponses complètes</p>
        <p class="mt-1 text-2xl font-semibold text-green-700">{{ $summary['completed'] }}</p>
    </div>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <p class="text-sm text-gray-500">Réponses à compléter</p>
        <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $summary['incomplete'] }}</p>
    </div>
</div>

<div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr><th class="px-4 py-3">Jour</th><th class="px-4 py-3">Menu 1</th><th class="px-4 py-3">Menu 2</th><th class="px-4 py-3">Aucun repas</th><th class="px-4 py-3">Sans réponse</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($summary['perDay'] as $row)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $row['day']->day_name }}</td>
                        <td class="px-4 py-3">{{ $row['counts']['menu_1'] }}</td>
                        <td class="px-4 py-3">{{ $row['counts']['menu_2'] }}</td>
                        <td class="px-4 py-3">{{ $row['counts']['no_meal'] }}</td>
                        <td class="px-4 py-3">{{ $row['counts']['no_response'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-4 py-3">
        <h4 class="font-semibold text-gray-900">Réponses incomplètes</h4>
    </div>
    <div class="divide-y divide-gray-100">
        @forelse($summary['pending'] as $user)
            <div class="flex items-center justify-between px-4 py-3 text-sm">
                <span class="font-medium text-gray-800">{{ $user->name }}</span>
                <span class="text-gray-500">{{ $user->departments->first()?->name ?? 'Non affecté' }}</span>
            </div>
        @empty
            <p class="px-4 py-6 text-sm text-gray-500">Tous les utilisateurs concernés ont répondu pour chaque jour.</p>
        @endforelse
    </div>
</section>
@endsection
