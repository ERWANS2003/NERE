@extends('layouts.portal')

@section('titre', 'Modifier ' . $automation->name)

@php
    $seed = [
        'name'          => $automation->name,
        'description'   => $automation->description ?? '',
        'trigger_event' => $automation->trigger_event ?? '',
        'conditions'    => array_map(fn ($c) => [
            'field'    => $c['field'] ?? '',
            'operator' => $c['operator'] ?? '=',
            'value'    => $c['value'] ?? '',
        ], $automation->conditions ?? []),
        'actions'       => array_map(fn ($a) => [
            'type' => $a['type'] ?? '',
            'data' => $a['data'] ?? [],
        ], $automation->actions ?? []),
        'is_active'     => (bool) $automation->is_active,
    ];
@endphp

@section('contenu')
<div class="max-w-7xl mx-auto" x-data="workflowBuilder(@json($seed))">
    {{-- Header --}}
    <div class="mb-8">
        <nav class="mb-4">
            <ol class="flex items-center gap-2 text-sm text-gray-600">
                <li><a href="{{ route('automations.index') }}" class="hover:text-accent-600">Automations</a></li>
                <li><x-icon name="chevron-right" size="xs" /></li>
                <li>
                    <a href="{{ route('automations.show', $automation) }}" class="hover:text-accent-600">{{ $automation->name }}</a>
                </li>
                <li><x-icon name="chevron-right" size="xs" /></li>
                <li class="text-gray-900 font-medium">Modifier</li>
            </ol>
        </nav>

        <h1 class="text-3xl font-bold text-gray-900">Modifier l'Automation</h1>
        <p class="text-gray-600 mt-2">Mettez à jour le déclenchement, les conditions et les actions.</p>
    </div>

    <form @submit.prevent="submitAutomation" class="grid grid-cols-3 gap-8">
        <div class="col-span-2 space-y-6">
            <div x-show="error" x-cloak role="alert"
                 class="flex gap-2.5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" />
                <span x-text="error"></span>
            </div>

            {{-- Step 1: Basic info --}}
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-accent-500 text-white flex items-center justify-center font-bold">1</div>
                    <h2 class="text-xl font-bold text-gray-900">Informations de Base</h2>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nom de l'Automation <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="automation.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500"
                               placeholder="Ex: Auto-escalade tickets critiques">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea x-model="automation.description" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500"
                                  placeholder="Décrivez ce que fait cette automation..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Step 2: Trigger --}}
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-accent-500 text-white flex items-center justify-center font-bold">2</div>
                    <h2 class="text-xl font-bold text-gray-900">Déclencheur</h2>
                </div>

                <p class="text-sm text-gray-600 mb-4">Quand cette automation doit-elle s'exécuter ?</p>

                <div class="grid grid-cols-2 gap-3">
                    <template x-for="(event, key) in availableEvents" :key="key">
                        <button type="button"
                                @click="automation.trigger_event = key"
                                :class="automation.trigger_event === key ? 'border-accent-500 bg-accent-50' : 'border-gray-200 hover:border-accent-300'"
                                class="flex items-center gap-3 p-4 border-2 rounded-lg transition text-left">
                            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                {{-- `event` is an Alpine loop variable, not a PHP one, so the
                                     trigger icon is deliberately static rather than bound. --}}
                                <x-icon name="lightning" size="md" class="text-blue-600" />
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900" x-text="event.name"></div>
                                <div class="text-xs text-gray-600" x-text="key"></div>
                            </div>
                            <div x-show="automation.trigger_event === key" class="ml-auto">
                                <x-icon name="check-circle" size="md" class="text-accent-600" />
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step 3: Conditions --}}
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-accent-500 text-white flex items-center justify-center font-bold">3</div>
                    <h2 class="text-xl font-bold text-gray-900">Conditions (IF)</h2>
                </div>

                <p class="text-sm text-gray-600 mb-4">Les conditions qui doivent être remplies</p>

                <div class="space-y-3">
                    <template x-for="(condition, index) in automation.conditions" :key="index">
                        <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-lg">
                            <select x-model="condition.field"
                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">
                                <option value="">-- Choisir un champ --</option>
                                <template x-for="(field, key) in availableFields" :key="key">
                                    <option :value="key" x-text="field.name"></option>
                                </template>
                            </select>

                            <select x-model="condition.operator"
                                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">
                                <option value="=">égal à</option>
                                <option value="!=">différent de</option>
                                <option value=">">supérieur à</option>
                                <option value="<">inférieur à</option>
                                <option value=">=">supérieur ou égal</option>
                                <option value="<=">inférieur ou égal</option>
                                <option value="contains">contient</option>
                                <option value="starts_with">commence par</option>
                                <option value="ends_with">finit par</option>
                                <option value="in">dans la liste</option>
                                <option value="not_in">n'est pas dans la liste</option>
                            </select>

                            <input type="text" x-model="condition.value" placeholder="Valeur"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">

                            <button type="button" @click="removeCondition(index)"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                <x-icon name="trash" size="sm" />
                                <span class="sr-only">Supprimer la condition</span>
                            </button>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addCondition"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-accent-600 hover:bg-accent-50 rounded-lg transition font-medium">
                    <x-icon name="plus" size="sm" />
                    Ajouter une Condition
                </button>
            </div>

            {{-- Step 4: Actions --}}
            <div class="bg-white rounded-lg shadow-sm border-2 border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 rounded-full bg-accent-500 text-white flex items-center justify-center font-bold">4</div>
                    <h2 class="text-xl font-bold text-gray-900">Actions (THEN)</h2>
                </div>

                <p class="text-sm text-gray-600 mb-4">Que faire quand les conditions sont remplies ?</p>

                <div class="space-y-3">
                    <template x-for="(action, index) in automation.actions" :key="index">
                        <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 space-y-3">
                                    <select x-model="action.type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent-500">
                                        <option value="">-- Choisir une action --</option>
                                        <template x-for="(act, key) in availableActions" :key="key">
                                            <option :value="key" x-text="act.name"></option>
                                        </template>
                                    </select>

                                    <div x-show="action.type" class="space-y-2">
                                        <template x-if="action.type === 'assign_ticket'">
                                            <input type="number" x-model="action.data.user_id" placeholder="User ID"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </template>

                                        <template x-if="action.type === 'change_status'">
                                            <input type="number" x-model="action.data.status_id" placeholder="Status ID"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </template>

                                        <template x-if="action.type === 'change_priority'">
                                            <input type="number" x-model="action.data.priority_id" placeholder="Priority ID"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </template>

                                        <template x-if="action.type === 'send_notification'">
                                            <div class="space-y-2">
                                                <input type="text" x-model="action.data.recipient" placeholder="Destinataire (email, manager, assignee...)"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <textarea x-model="action.data.message" placeholder="Message" rows="2"
                                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                            </div>
                                        </template>

                                        <template x-if="action.type === 'create_ticket'">
                                            <div class="space-y-2">
                                                <input type="text" x-model="action.data.title" placeholder="Titre"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <input type="number" x-model="action.data.category_id" placeholder="Category ID"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <textarea x-model="action.data.description" placeholder="Description" rows="2"
                                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                            </div>
                                        </template>

                                        <template x-if="action.type === 'add_comment'">
                                            <textarea x-model="action.data.content" placeholder="Contenu du commentaire" rows="2"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                        </template>

                                        <template x-if="action.type === 'escalate'">
                                            <input type="number" x-model="action.data.level" placeholder="Niveau (1, 2, 3...)"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        </template>

                                        <template x-if="action.type === 'send_email'">
                                            <div class="space-y-2">
                                                <input type="text" x-model="action.data.to" placeholder="Destinataire"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <input type="text" x-model="action.data.subject" placeholder="Objet"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                                <textarea x-model="action.data.body" placeholder="Corps du message" rows="3"
                                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <button type="button" @click="removeAction(index)"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                    <x-icon name="trash" size="sm" />
                                    <span class="sr-only">Supprimer l'action</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <button type="button" @click="addAction"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 text-green-600 hover:bg-green-50 rounded-lg transition font-medium">
                    <x-icon name="plus" size="sm" />
                    Ajouter une Action
                </button>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('automations.show', $automation) }}" class="px-6 py-2 text-gray-700 hover:text-gray-900">
                    Annuler
                </a>
                <button type="submit" :disabled="saving" class="btn-primary px-8 py-2 rounded-lg disabled:opacity-60">
                    <x-icon name="check" size="sm" />
                    <span x-text="saving ? 'Enregistrement…' : 'Enregistrer'"></span>
                </button>
            </div>
        </div>

        {{-- Preview sidebar --}}
        <div class="col-span-1">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-4">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Prévisualisation</h3>

                <div class="space-y-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-600 mb-1">Nom:</div>
                        <div class="font-semibold text-gray-900" x-text="automation.name || 'Sans nom'"></div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-600 mb-1">Déclencheur:</div>
                        <div class="font-semibold text-gray-900" x-text="availableEvents[automation.trigger_event]?.name || 'Aucun'"></div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-600 mb-1">Conditions:</div>
                        <div class="font-semibold text-gray-900">
                            <span x-text="automation.conditions.length"></span> condition(s)
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-600 mb-1">Actions:</div>
                        <div class="font-semibold text-gray-900">
                            <span x-text="automation.actions.length"></span> action(s)
                        </div>
                    </div>

                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="automation.is_active"
                                   class="w-4 h-4 text-accent-600 border-gray-300 rounded focus:ring-accent-500">
                            <span class="text-gray-700">Activer l'automation</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-xs font-semibold text-gray-600 mb-3">WORKFLOW VISUAL</div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 p-2 bg-blue-50 rounded text-xs">
                            <x-icon name="lightning" size="xs" class="text-blue-600" />
                            <span class="font-medium">WHEN: <span x-text="automation.trigger_event || 'event'"></span></span>
                        </div>
                        <div class="flex items-center gap-2 p-2 bg-amber-50 rounded text-xs">
                            <x-icon name="filter" size="xs" class="text-amber-600" />
                            <span class="font-medium">IF: <span x-text="automation.conditions.length + ' condition(s)'"></span></span>
                        </div>
                        <div class="flex items-center gap-2 p-2 bg-green-50 rounded text-xs">
                            <x-icon name="zap" size="xs" class="text-green-600" />
                            <span class="font-medium">THEN: <span x-text="automation.actions.length + ' action(s)'"></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function workflowBuilder(seed) {
    return {
        automation: {
            name: seed.name ?? '',
            description: seed.description ?? '',
            trigger_event: seed.trigger_event ?? '',
            conditions: seed.conditions ?? [],
            actions: seed.actions ?? [],
            is_active: seed.is_active ?? false
        },

        availableEvents: @json($availableEvents),
        availableActions: @json($availableActions),
        availableFields: @json($availableFields),

        saving: false,
        error: '',

        addCondition() {
            this.automation.conditions.push({ field: '', operator: '=', value: '' });
        },

        removeCondition(index) {
            this.automation.conditions.splice(index, 1);
        },

        addAction() {
            this.automation.actions.push({ type: '', data: {} });
        },

        removeAction(index) {
            this.automation.actions.splice(index, 1);
        },

        async submitAutomation() {
            this.error = '';

            if (!this.automation.name || !this.automation.trigger_event) {
                this.error = 'Veuillez renseigner le nom et le déclencheur.';
                return;
            }

            if (this.automation.conditions.length === 0) {
                this.error = 'Ajoutez au moins une condition.';
                return;
            }

            if (this.automation.actions.length === 0) {
                this.error = 'Ajoutez au moins une action.';
                return;
            }

            this.saving = true;

            try {
                const response = await fetch('{{ route('automations.update', $automation) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(Object.assign({ _method: 'PUT' }, this.automation))
                });

                if (response.ok) {
                    window.location.href = '{{ route('automations.show', $automation) }}';
                    return;
                }

                const payload = await response.json().catch(() => ({}));
                this.error = Object.values(payload.errors ?? {}).flat().join(' ')
                    || 'Erreur lors de la mise à jour.';
            } catch (e) {
                this.error = 'Erreur réseau lors de la mise à jour.';
            } finally {
                this.saving = false;
            }
        }
    };
}
</script>
@endpush

@endsection
