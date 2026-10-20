@php($selectedPermissions = array_map('intval', is_array($selectedPermissions) ? $selectedPermissions : []))

<section aria-labelledby="permissions-heading">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h3 id="permissions-heading" class="text-base font-semibold text-gray-900">Permissions</h3>
            <p class="mt-1 text-sm text-gray-600">Choisissez les accès correspondant aux responsabilités de ce rôle.</p>
        </div>
        <p class="text-sm font-medium text-gray-700" aria-live="polite">
            <span data-selected-count>{{ count($selectedPermissions) }}</span> sélectionnée(s)
        </p>
    </div>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
        <label class="relative flex-1">
            <span class="sr-only">Rechercher une permission</span>
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
            <input type="search" data-permission-search placeholder="Rechercher par nom ou description…"
                   class="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-nere-500 focus:outline-none focus:ring-2 focus:ring-nere-500">
        </label>
        <div class="flex gap-2">
            <button type="button" data-select-visible data-label-all="Tout sélectionner" data-label-filtered="Sélectionner les résultats" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-nere-500">
                Tout sélectionner
            </button>
            <button type="button" data-clear-visible data-label-all="Tout désélectionner" data-label-filtered="Désélectionner les résultats" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-nere-500">
                Tout désélectionner
            </button>
        </div>
    </div>

    @if($permissionGroups->isEmpty())
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <div class="flex gap-3">
                <i class="fas fa-circle-info mt-0.5" aria-hidden="true"></i>
                <div>
                    <p class="font-semibold">Le catalogue des permissions est vide.</p>
                    <p class="mt-1">Relancez les migrations de l’application pour initialiser les permissions disponibles.</p>
                </div>
            </div>
        </div>
    @else
        <div class="mt-5 space-y-4" data-permission-list>
            @foreach($permissionGroups as $group => $permissions)
                <fieldset class="overflow-hidden rounded-lg border border-gray-200" data-permission-group>
                    <legend class="sr-only">{{ $group }}</legend>
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <h4 class="text-sm font-semibold text-gray-800">{{ $group }}</h4>
                    </div>
                    <div class="grid gap-2 p-3 sm:grid-cols-2">
                        @foreach($permissions as $permission)
                            <label for="permission-{{ $permission->id }}"
                                   data-permission-option
                                   data-search="{{ mb_strtolower($permission->nom . ' ' . $permission->slug . ' ' . $permission->description) }}"
                                   class="flex cursor-pointer gap-3 rounded-md border border-transparent p-3 transition hover:border-gray-200 hover:bg-gray-50 has-[:checked]:border-nere-200 has-[:checked]:bg-nere-50">
                                <input id="permission-{{ $permission->id }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                       @checked(in_array($permission->id, $selectedPermissions, true))
                                       class="mt-0.5 rounded border-gray-300 text-nere-600 focus:ring-nere-500">
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-gray-800">{{ $permission->nom }}</span>
                                    <span class="mt-0.5 block text-xs leading-5 text-gray-600">{{ $permission->description }}</span>
                                    <code class="mt-1 inline-block text-xs text-gray-500">{{ $permission->slug }}</code>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <p class="hidden rounded-md bg-gray-50 p-4 text-center text-sm text-gray-600" data-no-permission-results>
                Aucune permission ne correspond à cette recherche.
            </p>
        </div>
    @endif

    @error('permissions')
        <p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror
    @error('permissions.*')
        <p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const list = document.querySelector('[data-permission-list]');
    if (!list) return;

    const search = document.querySelector('[data-permission-search]');
    const count = document.querySelector('[data-selected-count]');
    const noResults = document.querySelector('[data-no-permission-results]');
    const selectButton = document.querySelector('[data-select-visible]');
    const clearButton = document.querySelector('[data-clear-visible]');
    const options = [...list.querySelectorAll('[data-permission-option]')];
    const groups = [...list.querySelectorAll('[data-permission-group]')];
    const updateCount = () => {
        count.textContent = list.querySelectorAll('input[type="checkbox"]:checked').length;
    };

    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        selectButton.textContent = query ? selectButton.dataset.labelFiltered : selectButton.dataset.labelAll;
        clearButton.textContent = query ? clearButton.dataset.labelFiltered : clearButton.dataset.labelAll;
        options.forEach((option) => {
            const visible = option.dataset.search.includes(query);
            option.classList.toggle('hidden', !visible);
            if (visible) visibleCount++;
        });
        groups.forEach((group) => {
            group.classList.toggle('hidden', !group.querySelector('[data-permission-option]:not(.hidden)'));
        });
        noResults.classList.toggle('hidden', visibleCount > 0);
    });

    list.addEventListener('change', updateCount);
    selectButton.addEventListener('click', () => {
        options.filter((option) => !option.classList.contains('hidden'))
            .forEach((option) => { option.querySelector('input').checked = true; });
        updateCount();
    });
    clearButton.addEventListener('click', () => {
        options.filter((option) => !option.classList.contains('hidden'))
            .forEach((option) => { option.querySelector('input').checked = false; });
        updateCount();
    });
});
</script>
@endpush
