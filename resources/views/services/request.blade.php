@extends('layouts.app-new')

@section('titre', $service->name)

@section('contenu')
<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <ol class="flex items-center gap-2 text-sm text-gray-600">
            <li><a href="{{ route('services.index') }}" class="hover:text-accent-600">Catalogue</a></li>
            <li><x-icon name="chevron-right" size="xs" /></li>
            <li class="text-gray-900 font-medium">{{ $service->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-3 gap-8">
        <!-- Formulaire Principal -->
        <div class="col-span-2">
            <form action="{{ route('services.submit', $service) }}" method="POST" 
                  class="bg-white rounded-lg shadow-sm border border-gray-200 p-8"
                  x-data="serviceRequestForm()">
                @csrf

                <!-- Header -->
                <div class="flex items-start gap-4 mb-6 pb-6 border-b border-gray-200">
                    <div class="w-16 h-16 rounded-lg bg-accent-100 flex items-center justify-center flex-shrink-0">
                        <x-icon :name="$service->icon ?? 'service'" size="xl" class="text-accent-600" />
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $service->name }}</h1>
                        <p class="text-gray-600">{{ $service->description }}</p>
                    </div>
                </div>

                <!-- Formulaire Dynamique -->
                <div class="space-y-6">
                    @foreach($service->request_form as $field)
                    <div class="form-field">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ $field['label'] }}
                            @if($field['required'] ?? false)
                            <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if($field['type'] === 'text')
                        <input 
                            type="text" 
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name']) }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                            placeholder="{{ $field['placeholder'] ?? '' }}">

                        @elseif($field['type'] === 'textarea')
                        <textarea 
                            name="{{ $field['name'] }}"
                            rows="{{ $field['rows'] ?? 4 }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                            placeholder="{{ $field['placeholder'] ?? '' }}">{{ old($field['name']) }}</textarea>

                        @elseif($field['type'] === 'select')
                        <select 
                            name="{{ $field['name'] }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500">
                            <option value="">-- Sélectionner --</option>
                            @foreach($field['options'] as $option)
                            <option value="{{ $option }}" {{ old($field['name']) == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                            @endforeach
                        </select>

                        @elseif($field['type'] === 'date')
                        <input 
                            type="date" 
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name']) }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500">

                        @elseif($field['type'] === 'number')
                        <input 
                            type="number" 
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name']) }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                            placeholder="{{ $field['placeholder'] ?? '' }}">

                        @elseif($field['type'] === 'email')
                        <input 
                            type="email" 
                            name="{{ $field['name'] }}"
                            value="{{ old($field['name']) }}"
                            {{ ($field['required'] ?? false) ? 'required' : '' }}
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500 focus:border-accent-500"
                            placeholder="{{ $field['placeholder'] ?? '' }}">
                        @endif

                        @error($field['name'])
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('services.index') }}" class="px-6 py-2 text-gray-700 hover:text-gray-900">
                        Annuler
                    </a>
                    <button type="submit" class="btn-primary px-8 py-2 rounded-lg">
                        <x-icon name="check" size="sm" />
                        Soumettre la Demande
                    </button>
                </div>
            </form>
        </div>

        <!-- Sidebar Info -->
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Informations</h3>

                <div class="space-y-4">
                    <!-- Temps Estimé -->
                    @if($service->estimated_time)
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <x-icon name="clock" size="md" class="text-blue-600" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Temps Estimé</div>
                            <div class="text-sm text-gray-600 mt-1">
                                ~{{ round($service->estimated_time / 60, 1) }} heure(s)
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Approbation -->
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg {{ $service->requires_approval ? 'bg-amber-100' : 'bg-green-100' }} flex items-center justify-center flex-shrink-0">
                            <x-icon :name="$service->requires_approval ? 'shield-check' : 'lightning'" 
                                    size="md" 
                                    class="{{ $service->requires_approval ? 'text-amber-600' : 'text-green-600' }}" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Approbation</div>
                            <div class="text-sm text-gray-600 mt-1">
                                @if($service->requires_approval)
                                Requiert validation managériale
                                @else
                                Traitement automatique
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Catégorie -->
                    @if($service->category)
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <x-icon name="tag" size="md" class="text-purple-600" />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-700">Catégorie</div>
                            <div class="text-sm text-gray-600 mt-1">{{ $service->category->name }}</div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Workflow si approbation -->
                @if($service->requires_approval && $service->approval_workflow)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-3">Workflow d'Approbation</h4>
                    <div class="space-y-2">
                        @foreach($service->approval_workflow as $step)
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <x-icon name="check-circle" size="xs" class="text-gray-400" />
                            <span>{{ $step['name'] ?? $step }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Aide -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Besoin d'aide ?</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Consultez notre base de connaissances ou contactez le support
                    </p>
                    <a href="{{ route('knowledge.index') }}" class="text-sm text-accent-600 hover:text-accent-700 font-medium">
                        Voir la documentation →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function serviceRequestForm() {
    return {
        // Logique du formulaire si nécessaire
    };
}
</script>
@endpush
@endsection
