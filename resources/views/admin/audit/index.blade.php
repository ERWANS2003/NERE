@extends('admin.layout')

@section('title', 'Journal d’audit')

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Activité administrative</h2>
        <p class="mt-1 text-sm text-gray-600">Historique en lecture seule des opérations sensibles, avec auteur et valeurs modifiées.</p>
    </div>
</div>

<form method="GET" class="mb-6 grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 md:grid-cols-6">
    <input name="search" value="{{ request('search') }}" placeholder="Action ou cible…" class="md:col-span-2">
    <select name="category"><option value="">Toutes les catégories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>@endforeach</select>
    <select name="actor"><option value="">Tous les auteurs</option>@foreach($actors as $actor)<option value="{{ $actor->id }}" @selected((string) request('actor') === (string) $actor->id)>{{ $actor->name }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" aria-label="Depuis">
    <div class="flex gap-2"><input type="date" name="to" value="{{ request('to') }}" aria-label="Jusqu’au"><button class="bouton-primaire" aria-label="Filtrer"><x-icone nom="filter" /></button></div>
</form>

<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="w-full text-sm">
        <thead><tr><th class="px-4 py-3 text-left">Date</th><th class="px-4 py-3 text-left">Auteur</th><th class="px-4 py-3 text-left">Action</th><th class="px-4 py-3 text-left">Cible</th><th class="px-4 py-3 text-left">Changements</th></tr></thead>
        <tbody class="divide-y divide-gray-100">
        @forelse($logs as $log)
            <tr>
                <td class="whitespace-nowrap px-4 py-3 text-gray-600"><time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('d/m/Y H:i:s') }}</time></td>
                <td class="px-4 py-3"><span class="font-medium">{{ $log->actor?->name ?? 'Système' }}</span><span class="block text-xs text-gray-500">{{ $log->ip_address }}</span></td>
                <td class="px-4 py-3"><span class="rounded bg-gray-100 px-2 py-1 text-xs font-semibold">{{ $log->category }}</span><span class="mt-1 block">{{ $log->action }}</span></td>
                <td class="px-4 py-3">{{ $log->target_label ?: '—' }}</td>
                <td class="px-4 py-3">
                    @if($log->before || $log->after)
                    <details><summary class="cursor-pointer font-medium text-nere-700">Voir le détail</summary><div class="mt-2 grid gap-2 text-xs lg:grid-cols-2"><pre class="overflow-auto rounded bg-gray-950 p-3 text-gray-100">Avant
{{ json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><pre class="overflow-auto rounded bg-gray-950 p-3 text-gray-100">Après
{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details>
                    @else — @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-12 text-center text-gray-500">Aucune opération ne correspond à ces critères.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
