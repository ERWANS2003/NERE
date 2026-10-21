@php
    $breadcrumbs = [
        ['label' => 'Départements', 'url' => route('intranet.departments.manage.index')],
        ['label' => $department->name, 'url' => route('intranet.departments.manage.show', $department)],
        ['label' => 'Services'],
    ];
@endphp

<x-intranet-layout :title="'Services — '.$department->name" :breadcrumbs="$breadcrumbs">
    <x-intranet.page-header
        :title="'Services de '.$department->name"
        description="Configurez les destinations des services visibles dans la vitrine du département."
    >
        <x-slot:actions>
            <x-button :href="route('intranet.departments.manage.show', $department)" variant="secondary">
                Retour au département
            </x-button>
        </x-slot:actions>
    </x-intranet.page-header>

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4" role="alert">
            <ul class="list-disc space-y-1 pl-5 text-sm text-red-700">
                @foreach($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($services->isEmpty())
        <x-card class="mb-6 px-5 py-8 text-center">
            <p class="text-sm text-graphite-600">Aucun service n’est encore configuré pour ce département.</p>
        </x-card>
    @else
        <div class="space-y-5">
            @foreach($services as $service)
                @php
                    preg_match('~/intranet/forms/(\d+)~', (string) $service->link_url, $matches);
                    $linkedFormId = isset($matches[1]) ? (int) $matches[1] : null;
                @endphp
                <form method="POST"
                      action="{{ route('intranet.departments.manage.services.update', [$department, $service]) }}"
                      class="space-y-4 rounded-xl border border-graphite-200 bg-white p-5">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-etiquette :for="'service-name-'.$service->id">Nom du service</x-etiquette>
                            <x-champ :id="'service-name-'.$service->id" name="name" :value="$service->name" required maxlength="255" />
                        </div>
                        <div>
                            <x-etiquette :for="'service-icon-'.$service->id">Icône (facultatif)</x-etiquette>
                            <x-champ :id="'service-icon-'.$service->id" name="icon" :value="$service->icon" maxlength="100" />
                        </div>
                    </div>

                    <div>
                        <x-etiquette :for="'service-description-'.$service->id">Description</x-etiquette>
                        <textarea id="service-description-{{ $service->id }}" name="description" rows="2" maxlength="2000" class="champ">{{ $service->description }}</textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2" x-data="{ linkType: @js($service->link_type) }">
                        <div>
                            <x-etiquette :for="'service-type-'.$service->id">Type de destination</x-etiquette>
                            <select id="service-type-{{ $service->id }}" name="link_type" x-model="linkType" required class="champ">
                                <option value="form">Formulaire du département</option>
                                <option value="page">Page de l’intranet</option>
                                <option value="external">Site externe HTTPS</option>
                            </select>
                        </div>
                        <div x-show="linkType === 'form'" x-cloak>
                            <x-etiquette :for="'service-form-'.$service->id">Formulaire publié</x-etiquette>
                            <select id="service-form-{{ $service->id }}" name="form_id" class="champ">
                                <option value="">Choisir un formulaire</option>
                                @foreach($forms as $form)
                                    <option value="{{ $form->id }}" @selected($linkedFormId === $form->id)>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="linkType !== 'form'" x-cloak>
                            <x-etiquette :for="'service-url-'.$service->id">Adresse de destination</x-etiquette>
                            <x-champ :id="'service-url-'.$service->id" name="link_url"
                                     :value="$service->link_type === 'form' ? '' : $service->link_url"
                                     x-bind:type="linkType === 'external' ? 'url' : 'text'"
                                     x-bind:placeholder="linkType === 'page' ? '/intranet/...' : 'https://...'"
                                     maxlength="2048" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-graphite-100 pt-4">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-graphite-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked($service->is_active)
                                   class="rounded border-graphite-300 text-nere-700 focus:ring-nere-600">
                            Publier ce service
                        </label>
                        <x-button type="submit" variant="secondary">Enregistrer</x-button>
                    </div>
                </form>
                @if($service->is_active)
                    <form method="POST"
                          action="{{ route('intranet.departments.manage.services.destroy', [$department, $service]) }}"
                          class="flex justify-end -mt-4">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger"
                                  onclick="return confirm('Désactiver ce service ?')">
                            Désactiver
                        </x-button>
                    </form>
                @endif
            @endforeach
        </div>
    @endif

    <section class="mt-8 rounded-xl border border-graphite-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-graphite-900">Ajouter un service</h2>
        <form method="POST" action="{{ route('intranet.departments.manage.services.store', $department) }}"
              class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <x-etiquette for="new-service-name">Nom</x-etiquette>
                <x-champ id="new-service-name" name="name" :value="old('name')" required maxlength="255" />
            </div>
            <div>
                <x-etiquette for="new-service-icon">Icône (facultatif)</x-etiquette>
                <x-champ id="new-service-icon" name="icon" :value="old('icon')" maxlength="100" />
            </div>
            <div class="md:col-span-2">
                <x-etiquette for="new-service-description">Description</x-etiquette>
                <textarea id="new-service-description" name="description" rows="2" maxlength="2000" class="champ">{{ old('description') }}</textarea>
            </div>
            <div class="grid gap-4 md:grid-cols-2" x-data="{ linkType: @js(old('link_type', 'form')) }">
                <x-etiquette for="new-service-type">Type de destination</x-etiquette>
                <select id="new-service-type" name="link_type" x-model="linkType" required class="champ">
                    <option value="form">Formulaire du département</option>
                    <option value="page">Page de l’intranet</option>
                    <option value="external">Site externe HTTPS</option>
                </select>
                <div x-show="linkType === 'form'" x-cloak>
                    <x-etiquette for="new-service-form">Formulaire publié</x-etiquette>
                    <select id="new-service-form" name="form_id" class="champ">
                        <option value="">Choisir un formulaire</option>
                        @foreach($forms as $form)
                            <option value="{{ $form->id }}" @selected(old('form_id') == $form->id)>
                                {{ $form->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div x-show="linkType !== 'form'" x-cloak>
                    <x-etiquette for="new-service-url">Adresse de destination</x-etiquette>
                    <x-champ id="new-service-url" name="link_url" :value="old('link_url')"
                             x-bind:type="linkType === 'external' ? 'url' : 'text'"
                             x-bind:placeholder="linkType === 'page' ? '/intranet/...' : 'https://...'"
                             maxlength="2048" />
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm font-medium text-graphite-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                       class="rounded border-graphite-300 text-nere-700 focus:ring-nere-600">
                Publier ce service
            </label>
            <div class="flex justify-end">
                <x-button type="submit">Ajouter le service</x-button>
            </div>
        </form>
    </section>
</x-intranet-layout>
