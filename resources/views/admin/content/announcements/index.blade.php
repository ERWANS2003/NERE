@extends('admin.layout')

@section('title', 'Gestion des annonces')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Annonces</h1>
            <p class="mt-1 text-sm text-gray-600">Publier, modifier ou archiver les communications de l’intranet.</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="rounded bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Nouvelle annonce</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3">Annonce</th>
                    <th class="px-4 py-3">Public</th>
                    <th class="px-4 py-3">Priorité</th>
                    <th class="px-4 py-3">État</th>
                    <th class="px-4 py-3">Publication</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($announcements as $announcement)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">{{ $announcement->title }}</div>
                            <div class="text-xs text-gray-500">Par {{ $announcement->creator?->name ?? 'Compte supprimé' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $announcement->department?->name ?? 'Toute l’entreprise' }}</td>
                        <td class="px-4 py-3">{{ ['urgent' => 'Urgent', 'important' => 'Important', 'info' => 'Info'][$announcement->priority] ?? $announcement->priority }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded px-2 py-1 text-xs {{ $announcement->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $announcement->is_active ? 'Publiée' : 'Archivée' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $announcement->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="font-medium text-nere-700 hover:underline">Modifier</a>
                                @if($announcement->is_active)
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Archiver cette annonce ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="font-medium text-red-700 hover:underline">Archiver</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Aucune annonce enregistrée.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $announcements->links() }}</div>
@endsection
