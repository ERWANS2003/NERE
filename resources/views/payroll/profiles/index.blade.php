<x-intranet-layout title="Salariés · Paie">
    @include('payroll.partials.nav', ['title' => 'Salariés', 'subtitle' => $paidCount.' salarié(s) avec une fiche de paie active.'])

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <label class="block"><span class="etiquette">Rechercher</span><input type="search" name="q" value="{{ $search }}" placeholder="Nom ou matricule" class="champ"></label>
        <label class="block">
            <span class="etiquette">Fiche de paie</span>
            <select name="statut" class="champ">
                <option value="">Tous</option>
                <option value="avec" @selected($status === 'avec')>Avec fiche active</option>
                <option value="sans" @selected($status === 'sans')>Sans fiche</option>
            </select>
        </label>
        <button class="bouton-secondaire">Filtrer</button>
    </form>

    <section class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-graphite-50 text-graphite-600">
                <tr><th class="px-4 py-2">Salarié</th><th class="px-4 py-2">Matricule</th><th class="px-4 py-2">Catégorie</th><th class="px-4 py-2 text-right">Salaire de base</th><th class="px-4 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-graphite-100">
                @forelse($users as $user)
                    @php($profile = $user->payrollProfile)
                    <tr>
                        <td class="px-4 py-2"><span class="font-medium text-graphite-900">{{ $user->name }}</span><span class="block text-xs text-graphite-500">{{ $user->poste }}</span></td>
                        <td class="px-4 py-2">{{ $user->matricule }}</td>
                        <td class="px-4 py-2">{{ $profile?->category }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">
                            @if($profile?->active) {{ \App\Support\Fcfa::format($profile->base_salary) }}
                            @elseif($profile) <span class="text-xs text-graphite-500">fiche inactive</span>
                            @else <span class="text-xs text-graphite-400">aucune fiche</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right"><a href="{{ route('payroll.profiles.edit', $user) }}" class="font-medium text-nere-700 hover:underline">{{ $profile ? 'Modifier' : 'Créer la fiche' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-graphite-500">Aucun salarié trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <div class="mt-4">{{ $users->links() }}</div>
</x-intranet-layout>
