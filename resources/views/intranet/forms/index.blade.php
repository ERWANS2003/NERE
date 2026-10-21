@php
    $breadcrumbs = [
        ['label' => 'Formulaires'],
    ];
@endphp

<x-intranet-layout title="Formulaires" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        title="Formulaires"
        description="Consultez les formulaires publiés et gérez ceux de vos départements."
    >
        <x-slot:actions>
            @if($manageableDepartments->isNotEmpty())
                <x-button :href="route('intranet.forms.create')">Créer un formulaire</x-button>
            @endif
        </x-slot:actions>
    </x-intranet.page-header>

    <x-intranet.filters as-form action="{{ route('intranet.forms.index') }}" class="mb-5">
        <label for="department" class="text-sm font-medium text-graphite-700">Département</label>
        <select id="department" name="department" class="champ max-w-xs">
            <option value="">Tous les départements</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected(request('department') == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Filtrer</x-button>
    </x-intranet.filters>

    @if($forms->isEmpty())
        <x-card class="px-6 py-12 text-center">
            <h2 class="text-base font-semibold text-graphite-900">Aucun formulaire disponible</h2>
            <p class="mt-2 text-sm text-graphite-600">
                Les formulaires publiés apparaîtront ici. Les responsables peuvent en créer pour leur département.
            </p>
        </x-card>
    @else
        <div class="overflow-hidden rounded-xl border border-graphite-200 bg-white">
            <x-intranet.table>
                <table class="w-full text-left text-sm">
                    <thead class="bg-graphite-50 text-xs uppercase tracking-wide text-graphite-600">
                        <tr>
                            <th scope="col" class="px-4 py-3">Formulaire</th>
                            <th scope="col" class="px-4 py-3">Département</th>
                            <th scope="col" class="px-4 py-3">Champs</th>
                            <th scope="col" class="px-4 py-3">État</th>
                            <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-graphite-100">
                        @foreach($forms as $form)
                            @php($canManage = in_array($form->department_id, $manageableDepartmentIds, true))
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-graphite-900">{{ $form->name }}</div>
                                    @if($form->description)
                                        <p class="mt-1 max-w-xl text-graphite-500">{{ $form->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">{{ $form->department->name }}</td>
                                <td class="px-4 py-4">{{ count(\App\Support\DynamicFormSchema::fields($form->form_schema)) }}</td>
                                <td class="px-4 py-4">
                                    <x-badge :type="$form->is_active ? 'success' : 'default'">
                                        {{ $form->is_active ? 'Publié' : 'Brouillon' }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($form->is_active)
                                            <x-button :href="route('intranet.forms.show', $form)" variant="secondary">
                                                Remplir
                                            </x-button>
                                        @endif
                                        @if($canManage)
                                            <x-button :href="route('intranet.forms.edit', $form)" variant="secondary">
                                                Modifier
                                            </x-button>
                                            <form method="POST" action="{{ route('intranet.forms.destroy', $form) }}"
                                                  onsubmit="return confirm('Supprimer ou archiver ce formulaire ?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-button type="submit" variant="danger">Supprimer</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-intranet.table>
        </div>

        <div class="mt-5">{{ $forms->links() }}</div>
    @endif
</x-intranet-layout>
