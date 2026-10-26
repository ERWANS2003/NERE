@php
    $breadcrumbs = [
        ['label' => 'Analytics'],
    ];
    $onglets = array_filter([
        'synthese' => 'Synthèse',
        'demandes' => 'Demandes',
        'support' => isset($sections['support']) ? 'Support IT' : null,
        'conges' => 'Congés',
        'contenus' => isset($sections['contenus']) ? 'Contenus et adoption' : null,
        'effectifs' => 'Effectifs',
    ]);
    $alerteStyles = [
        'critique' => ['bg-nere-50 border-nere-200 text-nere-800', 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z', 'Critique'],
        'alerte' => ['bg-amber-50 border-amber-200 text-amber-900', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'À surveiller'],
        'info' => ['bg-blue-50 border-blue-200 text-blue-900', 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'Information'],
        'ok' => ['bg-green-50 border-green-200 text-green-900', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'Tout va bien'],
    ];
    $heures = fn (?float $h) => $h === null ? '—' : ($h < 1 ? round($h * 60).' min' : ($h < 48 ? round($h, 1).' h' : round($h / 24, 1).' j'));
@endphp

<x-intranet-layout title="Analytics" :breadcrumbs="$breadcrumbs">

    {{-- En-tête, filtres et exports --}}
    <div class="mb-6 space-y-4">
        <div>
            <h1 class="mb-1 text-3xl font-bold text-graphite-900">Tableau de bord analytique</h1>
            <p class="text-graphite-600">{{ $scopeLabel }} · {{ $periodes[$jours] }} glissants, comparés à la période précédente</p>
        </div>

        <div class="intranet-filters items-center justify-between">
            <form method="GET" data-live-search class="flex flex-wrap items-center gap-2">
                <label for="periode" class="text-sm text-graphite-600">Période</label>
                <select id="periode" name="periode" class="champ w-auto py-2">
                    @foreach($periodes as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected($jours === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
                @if($departementOptions->isNotEmpty())
                    <label for="departement" class="text-sm text-graphite-600">Département</label>
                    <select id="departement" name="departement" class="champ w-auto max-w-xs py-2">
                        <option value="">{{ $isAdmin ? 'Toute l’entreprise' : 'Tous mes départements' }}</option>
                        @foreach($departementOptions as $option)
                            <option value="{{ $option->id }}" @selected($departementId === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            <div class="flex flex-wrap items-center gap-2" data-auto-refresh data-interval="60000">
                <a href="{{ route('intranet.analytics.export.pdf', $filtres) }}" class="bouton-secondaire">PDF</a>
                <a href="{{ route('intranet.analytics.export.xlsx', $filtres) }}" class="bouton-secondaire">Excel</a>
                <a href="{{ route('intranet.analytics.export.csv', $filtres) }}" class="bouton-secondaire">CSV</a>
                <a href="{{ route('intranet.analytics.export.json', $filtres) }}" class="bouton-secondaire">JSON</a>
                <a href="{{ route('intranet.analytics', $filtres) }}" class="bouton-secondaire">Actualiser</a>
                <button type="button" class="bouton-secondaire" data-auto-refresh-toggle aria-pressed="false">
                    Suspendre l’actualisation
                </button>
                <span class="sr-only" role="status" aria-live="polite" data-auto-refresh-status></span>
            </div>
        </div>
    </div>

    <div x-data="analyticsDashboard(@js($sections), @js(array_keys($onglets)))" x-init="init()">

        {{-- Onglets --}}
        <div class="mb-6 flex gap-2 overflow-x-auto pb-1" role="tablist" aria-label="Domaines analysés">
            @foreach($onglets as $cle => $libelle)
                <button
                    type="button"
                    role="tab"
                    id="onglet-{{ $cle }}"
                    aria-controls="panneau-{{ $cle }}"
                    :aria-selected="onglet === '{{ $cle }}'"
                    :class="onglet === '{{ $cle }}' ? 'intranet-filter-link intranet-filter-link-active' : 'intranet-filter-link'"
                    class="intranet-filter-link whitespace-nowrap"
                    @click="afficher('{{ $cle }}')"
                >{{ $libelle }}</button>
            @endforeach
        </div>

        {{-- ============================== Synthèse ============================== --}}
        <section id="panneau-synthese" role="tabpanel" aria-labelledby="onglet-synthese" x-show="onglet === 'synthese'" class="space-y-6">
            <div>
                <h2 class="mb-3 text-lg font-bold text-graphite-900">Points d’attention</h2>
                <ul class="grid gap-2 md:grid-cols-2">
                    @foreach($sections['synthese']['alertes'] as $alerte)
                        @php [$classes, $icone, $niveau] = $alerteStyles[$alerte['niveau']]; @endphp
                        <li class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $classes }}">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icone }}"/></svg>
                            <span><strong class="font-semibold">{{ $niveau }} :</strong> {{ $alerte['texte'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($isAdmin && $overview)
                <section aria-labelledby="analytics-overview-heading">
                    <h2 id="analytics-overview-heading" class="mb-3 text-lg font-bold text-graphite-900">Vue d’ensemble de l’entreprise</h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        @foreach([
                            ['label' => 'Départements actifs', 'value' => $overview['departements']],
                            ['label' => 'Employés actifs', 'value' => $overview['effectif']],
                            ['label' => 'Demandes enregistrées (total)', 'value' => $overview['demandes']],
                        ] as $item)
                            <div class="carte p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-graphite-500">{{ $item['label'] }}</p>
                                <p class="mt-1 text-2xl font-bold tabular-nums text-graphite-900">{{ number_format($item['value'], 0, ',', ' ') }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <div>
                <h2 class="mb-3 text-lg font-bold text-graphite-900">Indicateurs clés · {{ $periodes[$jours] }}</h2>
                @include('intranet.analytics.partials.kpis', ['kpis' => $sections['synthese']['kpis']])
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Demandes reçues</h3>
                    <p class="mb-3 text-xs text-graphite-500">Période actuelle comparée à la période précédente</p>
                    <div class="h-64"><canvas x-ref="syntheseDemandes" aria-label="Évolution des demandes reçues" role="img"></canvas></div>
                </div>
                @isset($sections['support'])
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Tickets IT créés et résolus</h3>
                        <p class="mb-3 text-xs text-graphite-500">Un écart durable entre les deux courbes fait grossir le stock</p>
                        <div class="h-64"><canvas x-ref="syntheseTickets" aria-label="Tickets créés et résolus" role="img"></canvas></div>
                    </div>
                @else
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Demandes en attente par ancienneté</h3>
                        <p class="mb-3 text-xs text-graphite-500">Stock actuel, quelle que soit la période</p>
                        <div class="h-64"><canvas x-ref="syntheseAnciennete" aria-label="Ancienneté des demandes en attente" role="img"></canvas></div>
                    </div>
                @endisset
            </div>
        </section>

        {{-- ============================== Demandes ============================== --}}
        @php $d = $sections['demandes']; @endphp
        <section id="panneau-demandes" role="tabpanel" aria-labelledby="onglet-demandes" x-show="onglet === 'demandes'" x-cloak class="space-y-6">
            @include('intranet.analytics.partials.kpis', ['kpis' => $d['kpis'], 'colonnes' => 'md:grid-cols-3 xl:grid-cols-6'])

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Volume de demandes</h3>
                    <p class="mb-3 text-xs text-graphite-500">Trait plein : période actuelle · pointillés : période précédente</p>
                    <div class="h-64"><canvas x-ref="demandesTendance" aria-label="Volume de demandes" role="img"></canvas></div>
                </div>
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Issue des demandes</h3>
                    <p class="mb-3 text-xs text-graphite-500">Statut actuel des demandes déposées sur chaque intervalle</p>
                    <div class="h-64"><canvas x-ref="demandesStatuts" aria-label="Statut des demandes par intervalle" role="img"></canvas></div>
                </div>
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">{{ $isAdmin && ! $departementId ? 'Demandes par département' : 'Demandes de vos départements' }}</h3>
                    <p class="mb-3 text-xs text-graphite-500">Sur la période</p>
                    <div class="h-64"><canvas x-ref="demandesDepartements" aria-label="Demandes par département" role="img"></canvas></div>
                </div>
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Ancienneté du stock en attente</h3>
                    <p class="mb-3 text-xs text-graphite-500">Demandes encore sans décision, aujourd’hui</p>
                    <div class="h-64"><canvas x-ref="demandesAnciennete" aria-label="Ancienneté des demandes en attente" role="img"></canvas></div>
                </div>
            </div>

            {{-- Carte de chaleur jour × heure --}}
            <div class="carte">
                <h3 class="font-bold text-graphite-900">Quand les demandes arrivent-elles ?</h3>
                <p class="mb-4 text-xs text-graphite-500">Nombre de demandes déposées par jour de la semaine et par heure · plus la case est foncée, plus il y en a</p>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] table-fixed border-separate" style="border-spacing: 2px" aria-label="Demandes par jour et par heure">
                        <thead>
                            <tr>
                                <th class="w-10"></th>
                                @foreach($d['heatmap']['heures'] as $heure)
                                    <th scope="col" class="text-[10px] font-medium text-graphite-500">{{ $heure % 3 === 0 ? $heure.'h' : '' }}<span class="sr-only">{{ $heure }} h</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($d['heatmap']['jours'] as $i => $jour)
                                <tr>
                                    <th scope="row" class="pr-2 text-right text-xs font-medium text-graphite-600">{{ $jour }}</th>
                                    @foreach($d['heatmap']['cellules'][$i] as $heure => $nombre)
                                        @php
                                            $ratio = $nombre / $d['heatmap']['max'];
                                            $ramp = ['#eef3fb', '#cde2fb', '#9ec5f4', '#6da7ec', '#3987e5', '#256abf', '#184f95', '#0d366b'];
                                            $couleur = $nombre === 0 ? $ramp[0] : $ramp[min(7, 1 + (int) floor($ratio * 6.999))];
                                        @endphp
                                        <td class="h-6 rounded-[3px]" style="background-color: {{ $couleur }}" title="{{ $jour }} {{ $heure }}h–{{ $heure + 1 }}h : {{ $nombre }} demande(s)">
                                            <span class="sr-only">{{ $jour }} {{ $heure }} h : {{ $nombre }}</span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 flex items-center gap-2 text-xs text-graphite-500" aria-hidden="true">
                    <span>0</span>
                    @foreach(['#eef3fb', '#cde2fb', '#9ec5f4', '#6da7ec', '#3987e5', '#256abf', '#184f95', '#0d366b'] as $c)
                        <span class="h-3 w-5 rounded-sm" style="background-color: {{ $c }}"></span>
                    @endforeach
                    <span>{{ $d['heatmap']['max'] }}</span>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-5">
                <div class="carte overflow-x-auto xl:col-span-3">
                    <h3 class="mb-3 font-bold text-graphite-900">Formulaires les plus sollicités</h3>
                    @if($d['formulaires'] === [])
                        <p class="text-sm text-graphite-500">Aucune demande sur la période.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase text-graphite-500">
                                <tr>
                                    <th class="py-2 pr-3">Formulaire</th>
                                    <th class="py-2 pr-3 text-right">Volume</th>
                                    <th class="py-2 pr-3 text-right">Approbation</th>
                                    <th class="py-2 pr-3 text-right">Délai médian</th>
                                    <th class="py-2 text-right">En attente</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-graphite-100">
                                @foreach($d['formulaires'] as $ligne)
                                    <tr>
                                        <td class="py-2 pr-3">
                                            <span class="font-medium text-graphite-900">{{ $ligne['nom'] }}</span>
                                            <span class="block text-xs text-graphite-500">{{ $ligne['departement'] }}</span>
                                        </td>
                                        <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['volume'] }}</td>
                                        <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['approbation'] === null ? '—' : $ligne['approbation'].' %' }}</td>
                                        <td class="py-2 pr-3 text-right tabular-nums">{{ $heures($ligne['delaiMedian']) }}</td>
                                        <td class="py-2 text-right tabular-nums">{{ $ligne['enAttente'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                <div class="carte overflow-x-auto xl:col-span-2">
                    <h3 class="mb-3 font-bold text-graphite-900">Valideurs les plus actifs</h3>
                    @if($d['agents'] === [])
                        <p class="text-sm text-graphite-500">Aucune décision sur la période.</p>
                    @else
                        <table class="w-full text-left text-sm">
                            <thead class="text-xs uppercase text-graphite-500">
                                <tr>
                                    <th class="py-2 pr-3">Valideur</th>
                                    <th class="py-2 pr-3 text-right">Traitées</th>
                                    <th class="py-2 pr-3 text-right">Approuvées</th>
                                    <th class="py-2 text-right">Délai médian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-graphite-100">
                                @foreach($d['agents'] as $ligne)
                                    <tr>
                                        <td class="py-2 pr-3 font-medium text-graphite-900">{{ $ligne['nom'] }}</td>
                                        <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['traitees'] }}</td>
                                        <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['approuvees'] }}</td>
                                        <td class="py-2 text-right tabular-nums">{{ $heures($ligne['delaiMedian']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </section>

        {{-- ============================== Support IT ============================== --}}
        @isset($sections['support'])
            @php $s = $sections['support']; @endphp
            <section id="panneau-support" role="tabpanel" aria-labelledby="onglet-support" x-show="onglet === 'support'" x-cloak class="space-y-6">
                @include('intranet.analytics.partials.kpis', ['kpis' => $s['kpis']])

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Tickets créés et résolus</h3>
                        <p class="mb-3 text-xs text-graphite-500">Sur la période</p>
                        <div class="h-64"><canvas x-ref="supportFlux" aria-label="Tickets créés et résolus" role="img"></canvas></div>
                    </div>
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Respect des engagements SLA</h3>
                        <p class="mb-3 text-xs text-graphite-500">Tickets créés sur la période dont l’échéance est connue</p>
                        <div class="h-64"><canvas x-ref="supportSla" aria-label="Respect des SLA" role="img"></canvas></div>
                    </div>
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Tickets par catégorie</h3>
                        <p class="mb-3 text-xs text-graphite-500">Créés sur la période</p>
                        <div class="h-64"><canvas x-ref="supportCategories" aria-label="Tickets par catégorie" role="img"></canvas></div>
                    </div>
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Stock ouvert par priorité</h3>
                        <p class="mb-3 text-xs text-graphite-500">Tickets non résolus aujourd’hui</p>
                        <div class="h-64"><canvas x-ref="supportPriorites" aria-label="Tickets ouverts par priorité" role="img"></canvas></div>
                    </div>
                </div>

                <div class="grid gap-6 xl:grid-cols-5">
                    <div class="carte xl:col-span-2">
                        <h3 class="font-bold text-graphite-900">Ancienneté des tickets ouverts</h3>
                        <p class="mb-3 text-xs text-graphite-500">Âge des tickets encore ouverts</p>
                        <div class="h-56"><canvas x-ref="supportAnciennete" aria-label="Ancienneté des tickets ouverts" role="img"></canvas></div>
                    </div>
                    <div class="carte overflow-x-auto xl:col-span-3">
                        <h3 class="mb-3 font-bold text-graphite-900">Charge des techniciens</h3>
                        @if($s['techniciens'] === [])
                            <p class="text-sm text-graphite-500">Aucun ticket ouvert ni résolu sur la période.</p>
                        @else
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase text-graphite-500">
                                    <tr>
                                        <th class="py-2 pr-3">Technicien</th>
                                        <th class="py-2 pr-3 text-right">Ouverts</th>
                                        <th class="py-2 pr-3 text-right">Dont urgents/hauts</th>
                                        <th class="py-2 pr-3 text-right">Résolus</th>
                                        <th class="py-2 text-right">MTTR</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-graphite-100">
                                    @foreach($s['techniciens'] as $ligne)
                                        <tr>
                                            <td class="py-2 pr-3 font-medium {{ $ligne['nom'] === 'Non assignés' ? 'italic text-nere-700' : 'text-graphite-900' }}">{{ $ligne['nom'] }}</td>
                                            <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['ouverts'] }}</td>
                                            <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['urgents'] }}</td>
                                            <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['resolus'] }}</td>
                                            <td class="py-2 text-right tabular-nums">{{ $heures($ligne['mttr']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </section>
        @endisset

        {{-- ============================== Congés ============================== --}}
        @php $c = $sections['conges']; @endphp
        <section id="panneau-conges" role="tabpanel" aria-labelledby="onglet-conges" x-show="onglet === 'conges'" x-cloak class="space-y-6">
            @include('intranet.analytics.partials.kpis', ['kpis' => $c['kpis'], 'colonnes' => 'md:grid-cols-3 xl:grid-cols-6'])

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Demandes de congé déposées</h3>
                    <p class="mb-3 text-xs text-graphite-500">Trait plein : période actuelle · pointillés : période précédente</p>
                    <div class="h-64"><canvas x-ref="congesTendance" aria-label="Demandes de congé" role="img"></canvas></div>
                </div>
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Jours approuvés par type d’absence</h3>
                    <p class="mb-3 text-xs text-graphite-500">Demandes déposées sur la période</p>
                    <div class="h-64"><canvas x-ref="congesTypes" aria-label="Jours approuvés par type" role="img"></canvas></div>
                </div>
            </div>

            <div class="carte overflow-x-auto">
                <h3 class="mb-3 font-bold text-graphite-900">Absences dans les 14 prochains jours</h3>
                @if($c['aVenir'] === [])
                    <p class="text-sm text-graphite-500">Aucune absence approuvée sur les deux prochaines semaines.</p>
                @else
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs uppercase text-graphite-500">
                            <tr>
                                <th class="py-2 pr-3">Collaborateur</th>
                                <th class="py-2 pr-3">Type</th>
                                <th class="py-2 pr-3">Du</th>
                                <th class="py-2 pr-3">Au</th>
                                <th class="py-2 text-right">Jours</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-graphite-100">
                            @foreach($c['aVenir'] as $ligne)
                                <tr>
                                    <td class="py-2 pr-3 font-medium text-graphite-900">{{ $ligne['nom'] }}</td>
                                    <td class="py-2 pr-3">{{ $ligne['type'] }}</td>
                                    <td class="py-2 pr-3 tabular-nums">{{ $ligne['du'] }}</td>
                                    <td class="py-2 pr-3 tabular-nums">{{ $ligne['au'] }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ rtrim(rtrim(number_format($ligne['jours'], 1, ',', ''), '0'), ',') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </section>

        {{-- ============================== Contenus ============================== --}}
        @isset($sections['contenus'])
            @php $k = $sections['contenus']; @endphp
            <section id="panneau-contenus" role="tabpanel" aria-labelledby="onglet-contenus" x-show="onglet === 'contenus'" x-cloak class="space-y-6">
                @include('intranet.analytics.partials.kpis', ['kpis' => $k['kpis'], 'colonnes' => 'md:grid-cols-3 xl:grid-cols-6'])

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="carte overflow-x-auto">
                        <h3 class="mb-3 font-bold text-graphite-900">Base de connaissances : avis des lecteurs</h3>
                        @if($k['articles'] === [])
                            <p class="text-sm text-graphite-500">Aucun avis enregistré pour l’instant.</p>
                        @else
                            <table class="w-full text-left text-sm">
                                <thead class="text-xs uppercase text-graphite-500">
                                    <tr>
                                        <th class="py-2 pr-3">Article</th>
                                        <th class="py-2 pr-3 text-right">Utile</th>
                                        <th class="py-2 pr-3 text-right">Pas utile</th>
                                        <th class="py-2 text-right">Satisfaction</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-graphite-100">
                                    @foreach($k['articles'] as $ligne)
                                        <tr>
                                            <td class="py-2 pr-3 font-medium text-graphite-900">{{ $ligne['titre'] }}</td>
                                            <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['utile'] }}</td>
                                            <td class="py-2 pr-3 text-right tabular-nums">{{ $ligne['inutile'] }}</td>
                                            <td class="py-2 text-right tabular-nums {{ $ligne['score'] < 50 ? 'font-semibold text-nere-700' : '' }}">{{ $ligne['score'] }} %</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    <div class="carte">
                        <h3 class="font-bold text-graphite-900">Ressources les plus téléchargées</h3>
                        <p class="mb-3 text-xs text-graphite-500">Depuis leur mise en ligne</p>
                        <div class="h-64"><canvas x-ref="contenusRessources" aria-label="Ressources les plus téléchargées" role="img"></canvas></div>
                    </div>
                </div>

                @if($k['menu'])
                    <div class="carte flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-bold text-graphite-900">Menu du personnel · {{ $k['menu']['titre'] }}</h3>
                            <p class="text-sm text-graphite-600">{{ $k['menu']['inscrits'] }} inscrit(s), soit {{ $k['menu']['participation'] ?? 0 }} % des employés actifs</p>
                        </div>
                        <div class="h-3 w-full max-w-sm overflow-hidden rounded-full bg-graphite-100" role="img" aria-label="Participation de {{ $k['menu']['participation'] ?? 0 }} %">
                            <div class="h-full rounded-full" style="width: {{ min(100, $k['menu']['participation'] ?? 0) }}%; background-color: #2a78d6"></div>
                        </div>
                    </div>
                @endif
            </section>
        @endisset

        {{-- ============================== Effectifs ============================== --}}
        @php $e = $sections['effectifs']; @endphp
        <section id="panneau-effectifs" role="tabpanel" aria-labelledby="onglet-effectifs" x-show="onglet === 'effectifs'" x-cloak class="space-y-6">
            @include('intranet.analytics.partials.kpis', ['kpis' => $e['kpis'], 'colonnes' => 'md:grid-cols-3 xl:grid-cols-5'])

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="carte xl:col-span-2">
                    <h3 class="font-bold text-graphite-900">{{ $isAdmin && ! $departementId ? 'Effectifs par département' : 'Effectifs de vos départements' }}</h3>
                    <p class="mb-3 text-xs text-graphite-500">Comptes actifs et désactivés rattachés à chaque département</p>
                    <div class="h-72"><canvas x-ref="effectifsDepartements" aria-label="Effectifs par département" role="img"></canvas></div>
                </div>
                <div class="carte">
                    <h3 class="font-bold text-graphite-900">Origine des comptes actifs</h3>
                    <p class="mb-3 text-xs text-graphite-500">Synchronisés depuis l’Active Directory ou créés localement</p>
                    <div class="h-72"><canvas x-ref="effectifsOrigine" aria-label="Origine des comptes" role="img"></canvas></div>
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
    <script>
        // Palette catégorielle validée (ordre fixe) + couleurs d'état réservées.
        const VIZ = {
            serie: ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'],
            reference: '#9aa3ab',
            bon: '#0ca30c',
            vigilance: '#fab219',
            critique: '#d03b3b',
            grille: 'rgba(27, 33, 38, 0.06)',
            texte: '#515c66',
        };

        function analyticsDashboard(sections, onglets) {
            return {
                onglet: 'synthese',
                graphiques: {},

                init() {
                    const demande = window.location.hash.replace('#', '');
                    if (onglets.includes(demande)) {
                        this.onglet = demande;
                    }
                    this.$nextTick(() => this.dessiner(this.onglet));
                },

                afficher(cle) {
                    this.onglet = cle;
                    history.replaceState(null, '', '#' + cle);
                    this.$nextTick(() => this.dessiner(cle));
                },

                // Les graphiques d'un onglet masqué n'ont pas de taille : on les crée à la première ouverture.
                dessiner(cle) {
                    if (typeof window.Chart === 'undefined' || this.graphiques[cle]) {
                        return;
                    }
                    this.graphiques[cle] = true;
                    const s = sections;

                    if (cle === 'synthese') {
                        this.tendance('syntheseDemandes', s.demandes.tendance, 'Demandes');
                        if (s.support) {
                            this.lignes('supportFluxSynthese', 'syntheseTickets', s.support.flux);
                        } else {
                            this.barres('syntheseAnciennete', s.demandes.anciennete, VIZ.serie[0]);
                        }
                    }
                    if (cle === 'demandes') {
                        this.tendance('demandesTendance', s.demandes.tendance, 'Demandes');
                        this.empile('demandesStatuts', s.demandes.statutsParPeriode, [VIZ.bon, VIZ.critique, VIZ.vigilance]);
                        this.barres('demandesDepartements', @js($charts['parDepartement']), VIZ.serie[0], true);
                        this.barres('demandesAnciennete', s.demandes.anciennete, VIZ.serie[0]);
                    }
                    if (cle === 'support' && s.support) {
                        this.lignes('supportFlux', 'supportFlux', s.support.flux);
                        this.empile('supportSla', {
                            labels: s.support.sla.labels,
                            series: { 'Respectés': s.support.sla.respectes, 'Dépassés': s.support.sla.depasses },
                        }, [VIZ.bon, VIZ.critique], true);
                        this.barres('supportCategories', s.support.parCategorie, VIZ.serie[0], true);
                        this.barres('supportPriorites', s.support.ouvertsParPriorite, VIZ.serie[1], true);
                        this.barres('supportAnciennete', s.support.anciennete, VIZ.serie[0]);
                    }
                    if (cle === 'conges') {
                        this.tendance('congesTendance', s.conges.tendance, 'Demandes de congé');
                        this.barres('congesTypes', s.conges.joursParType, VIZ.serie[2], true, ' j');
                    }
                    if (cle === 'contenus' && s.contenus) {
                        this.barres('contenusRessources', {
                            labels: s.contenus.ressources.map(r => r.titre),
                            data: s.contenus.ressources.map(r => r.telechargements),
                        }, VIZ.serie[0], true);
                    }
                    if (cle === 'effectifs') {
                        this.empile('effectifsDepartements', {
                            labels: s.effectifs.parDepartement.labels,
                            series: { 'Actifs': s.effectifs.parDepartement.actifs, 'Désactivés': s.effectifs.parDepartement.inactifs },
                        }, [VIZ.serie[0], VIZ.reference], true);
                        this.anneau('effectifsOrigine', s.effectifs.origine, [VIZ.serie[0], VIZ.serie[1]]);
                    }
                },

                options(extra = {}) {
                    return {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false, labels: { color: VIZ.texte, usePointStyle: true, boxWidth: 8 } },
                            tooltip: { padding: 10, cornerRadius: 8 },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: VIZ.texte, maxRotation: 0, autoSkip: true } },
                            y: { beginAtZero: true, grid: { color: VIZ.grille }, border: { display: false }, ticks: { color: VIZ.texte, precision: 0 } },
                        },
                        ...extra,
                    };
                },

                canvas(ref) {
                    return this.$refs[ref] ?? null;
                },

                tendance(ref, data, libelle) {
                    const el = this.canvas(ref);
                    if (!el) return;
                    new window.Chart(el, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                { label: libelle + ' · période actuelle', data: data.actuelle, borderColor: VIZ.serie[0], backgroundColor: 'rgba(42, 120, 214, 0.10)', fill: true, borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 5 },
                                { label: 'Période précédente', data: data.precedente, borderColor: VIZ.reference, borderDash: [5, 4], borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 4, fill: false },
                            ],
                        },
                        options: this.options({ plugins: { ...this.options().plugins, legend: { ...this.options().plugins.legend, display: true, position: 'bottom' } } }),
                    });
                },

                lignes(_id, ref, data) {
                    const el = this.canvas(ref);
                    if (!el) return;
                    new window.Chart(el, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: Object.entries(data.series).map(([nom, valeurs], i) => ({
                                label: nom, data: valeurs, borderColor: VIZ.serie[i], backgroundColor: VIZ.serie[i],
                                borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 5,
                            })),
                        },
                        options: this.options({ plugins: { ...this.options().plugins, legend: { ...this.options().plugins.legend, display: true, position: 'bottom' } } }),
                    });
                },

                barres(ref, data, couleur, horizontal = false, suffixe = '') {
                    const el = this.canvas(ref);
                    if (!el) return;
                    const base = this.options({ indexAxis: horizontal ? 'y' : 'x' });
                    if (horizontal) {
                        base.scales = {
                            x: { beginAtZero: true, grid: { color: VIZ.grille }, border: { display: false }, ticks: { color: VIZ.texte, precision: 0 } },
                            y: { grid: { display: false }, ticks: { color: VIZ.texte, autoSkip: false } },
                        };
                    }
                    base.plugins.tooltip.callbacks = { label: (ctx) => ' ' + ctx.formattedValue + suffixe };
                    new window.Chart(el, {
                        type: 'bar',
                        data: { labels: data.labels, datasets: [{ data: data.data, backgroundColor: couleur, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 28 }] },
                        options: base,
                    });
                },

                empile(ref, data, couleurs, horizontal = false) {
                    const el = this.canvas(ref);
                    if (!el) return;
                    const base = this.options({ indexAxis: horizontal ? 'y' : 'x' });
                    base.plugins.legend = { ...base.plugins.legend, display: true, position: 'bottom' };
                    base.scales.x = { ...base.scales.x, stacked: true };
                    base.scales.y = { ...base.scales.y, stacked: true };
                    if (horizontal) {
                        base.scales.x = { ...base.scales.x, beginAtZero: true, grid: { color: VIZ.grille }, ticks: { color: VIZ.texte, precision: 0 } };
                        base.scales.y = { ...base.scales.y, grid: { display: false }, beginAtZero: undefined };
                    }
                    new window.Chart(el, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: Object.entries(data.series).map(([nom, valeurs], i) => ({
                                label: nom, data: valeurs, backgroundColor: couleurs[i], borderColor: '#ffffff', borderWidth: 1, borderRadius: 3, maxBarThickness: 32,
                            })),
                        },
                        options: base,
                    });
                },

                anneau(ref, data, couleurs) {
                    const el = this.canvas(ref);
                    if (!el) return;
                    new window.Chart(el, {
                        type: 'doughnut',
                        data: { labels: data.labels, datasets: [{ data: data.data, backgroundColor: couleurs, borderColor: '#ffffff', borderWidth: 2 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, animation: false, cutout: '62%',
                            plugins: { legend: { position: 'bottom', labels: { color: VIZ.texte, usePointStyle: true, boxWidth: 8 } } },
                        },
                    });
                },
            };
        }
    </script>
    @endpush

</x-intranet-layout>
