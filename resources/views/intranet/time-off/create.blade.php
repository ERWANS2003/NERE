@php
    $breadcrumbs = [
        ['label' => 'Mes congés', 'url' => route('intranet.time-off.index')],
        ['label' => 'Nouvelle demande'],
    ];
@endphp

<x-intranet-layout title="Demander un congé" :breadcrumbs="$breadcrumbs">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-graphite-900 mb-6">Demander un congé</h1>

        @if($errors->any())
            <div class="mb-6 rounded-lg bg-nere-50 border border-nere-200 text-nere-800 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('intranet.time-off.store') }}"
              x-data="congeForm()"
              class="bg-white rounded-xl border border-graphite-200 p-8 space-y-6">
            @csrf

            <div>
                <x-etiquette for="policy_id">Type de congé</x-etiquette>
                <select id="policy_id" name="policy_id" required class="champ"
                        x-model="policyId"
                        @change="majPreavis()">
                    <option value="">— Choisir —</option>
                    @foreach($policies as $policy)
                        <option value="{{ $policy->id }}"
                                data-preavis="{{ $policy->advance_notice_days }}"
                                data-solde="{{ optional($policy->balances->firstWhere('user_id', auth()->id()))->remaining_days }}"
                                @selected(old('policy_id') == $policy->id)>
                            {{ $policy->name }} ({{ $policy->annual_allocation }} j/an)
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-graphite-500" x-show="preavis > 0" x-cloak>
                    Préavis requis : <span x-text="preavis"></span> jour(s).
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <x-etiquette for="start_date">Date de début</x-etiquette>
                    <input type="date" id="start_date" name="start_date" required class="champ"
                           min="{{ now()->toDateString() }}"
                           x-model="debut" @change="if (fin < debut) fin = debut; calcul()"
                           value="{{ old('start_date') }}">
                </div>
                <div>
                    <x-etiquette for="end_date">Date de fin</x-etiquette>
                    <input type="date" id="end_date" name="end_date" required class="champ"
                           x-model="fin" :min="debut" @change="calcul()"
                           :disabled="demiJournee"
                           value="{{ old('end_date') }}">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_half_day" name="is_half_day" value="1"
                       x-model="demiJournee" @change="if (demiJournee) fin = debut; calcul()"
                       class="rounded border-graphite-300 text-nere-600 focus:ring-nere-600/30">
                <label for="is_half_day" class="text-sm text-graphite-700">Demi-journée (une seule date)</label>
            </div>

            <div class="rounded-lg bg-graphite-50 px-4 py-3 text-sm text-graphite-700" x-show="jours > 0" x-cloak>
                Durée estimée : <strong x-text="jours"></strong> jour(s) ouvré(s).
                <span class="text-graphite-500">(hors week-ends ; jours fériés non déduits)</span>
            </div>

            <div>
                <x-etiquette for="reason">Motif (facultatif)</x-etiquette>
                <textarea id="reason" name="reason" rows="3" maxlength="1000" class="champ">{{ old('reason') }}</textarea>
            </div>

            <div>
                <x-etiquette for="emergency_contact">Contact en cas d'urgence (facultatif)</x-etiquette>
                <x-champ id="emergency_contact" name="emergency_contact" :value="old('emergency_contact')" maxlength="255" />
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-bouton-primaire>Soumettre la demande</x-bouton-primaire>
                <a href="{{ route('intranet.time-off.index') }}" class="bouton-secondaire">Annuler</a>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        function congeForm() {
            return {
                policyId: '{{ old('policy_id') }}',
                debut: '{{ old('start_date') }}',
                fin: '{{ old('end_date') }}',
                demiJournee: {{ old('is_half_day') ? 'true' : 'false' }},
                jours: 0,
                preavis: 0,

                init() {
                    this.majPreavis();
                    this.calcul();
                },

                majPreavis() {
                    const opt = this.$el.querySelector(`#policy_id option[value="${this.policyId}"]`);
                    this.preavis = opt ? parseInt(opt.dataset.preavis || '0', 10) : 0;
                },

                // Compte les jours ouvrés (lundi-vendredi), demi-journée = 0,5.
                calcul() {
                    if (!this.debut || !this.fin) { this.jours = 0; return; }
                    const d = new Date(this.debut + 'T00:00:00');
                    const f = new Date(this.fin + 'T00:00:00');
                    if (f < d) { this.jours = 0; return; }

                    if (this.demiJournee) {
                        const jour = d.getDay();
                        this.jours = (jour === 0 || jour === 6) ? 0 : 0.5;
                        return;
                    }

                    let n = 0;
                    const cur = new Date(d);
                    while (cur <= f) {
                        const jour = cur.getDay();
                        if (jour !== 0 && jour !== 6) n++;
                        cur.setDate(cur.getDate() + 1);
                    }
                    this.jours = n;
                },
            };
        }
    </script>
    @endpush

</x-intranet-layout>
