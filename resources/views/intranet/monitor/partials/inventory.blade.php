{{-- Fiche SNMP d'un équipement (chargée à part, voir MonitorController::deviceSheet). --}}
@php
    $bar = fn (?float $percent, bool $higherIsBad = true) => match (true) {
        $percent === null => 'bg-graphite-300',
        $higherIsBad ? $percent >= 95 : $percent <= 5 => 'bg-red-600',
        $higherIsBad ? $percent >= 85 : $percent <= 15 => 'bg-amber-400',
        default => 'bg-green-600',
    };
    // Couleur réelle du toner (référence finissant par C, M, Y ou K, ou nom en clair).
    $tonerColor = fn (string $name) => match (true) {
        preg_match('/cyan|\dC\b|-C\b/i', $name) === 1 => 'bg-cyan-500',
        preg_match('/magenta|\dM\b|-M\b/i', $name) === 1 => 'bg-fuchsia-600',
        preg_match('/yellow|jaune|\dY\b|-Y\b/i', $name) === 1 => 'bg-yellow-400',
        preg_match('/black|noir|\dK\b|-K\b/i', $name) === 1 => 'bg-graphite-900',
        default => null,
    };
@endphp
@if(isset($info['error']))
    <p class="text-sm text-graphite-600">
        Fiche indisponible : {{ $info['error'] }}.
        Activez SNMP sur l’équipement (communauté « public » par défaut, ou celle indiquée dans les réglages du groupe) pour voir son modèle, ses interfaces et ses détails.
    </p>
@else
    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Identité</h3>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                @foreach($info['identity'] as $label => $value)
                    <dt class="text-graphite-500">{{ $label }}</dt>
                    <dd class="font-medium text-graphite-900">{{ $value }}</dd>
                @endforeach
            </dl>
        </div>

        @if(! empty($info['display']) || isset($info['pages']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Imprimante</h3>
                @if(! empty($info['display']))
                    <p class="rounded-lg bg-graphite-900 px-4 py-3 font-mono text-sm text-green-300" title="Texte affiché sur l’écran de l’imprimante">{{ implode(' · ', $info['display']) }}</p>
                @endif
                @if(isset($info['pages']))
                    <p class="mt-3 text-sm text-graphite-600">Compteur total : <span class="text-lg font-semibold text-graphite-900">{{ number_format($info['pages'], 0, ',', ' ') }}</span> pages</p>
                @endif
            </div>
        @endif

        @if(! empty($info['supplies']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Consommables</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($info['supplies'] as $supply)
                        <li>
                            <div class="flex justify-between"><span>{{ $supply['name'] }}</span><span class="font-semibold">{{ $supply['percent'] !== null ? $supply['percent'].' %' : $supply['state'] }}</span></div>
                            @if($supply['percent'] !== null)
                                <div class="mt-1 h-2 rounded-full bg-graphite-100"><div class="h-2 rounded-full {{ $supply['percent'] > 15 && $tonerColor($supply['name']) ? $tonerColor($supply['name']) : $bar((float) $supply['percent'], false) }}" style="width: {{ max(2, min(100, $supply['percent'])) }}%"></div></div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! empty($info['trays']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Papier</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($info['trays'] as $tray)
                        @php $percent = $tray['max'] > 0 && $tray['level'] !== null && $tray['level'] >= 0 ? round(100 * $tray['level'] / $tray['max']) : null; @endphp
                        <li>
                            <div class="flex justify-between"><span>{{ $tray['name'] }}</span><span class="font-semibold">{{ $percent !== null ? ($tray['level'] === 0 ? 'Vide' : $tray['level'].' / '.$tray['max'].' feuilles') : 'Inconnu' }}</span></div>
                            @if($percent !== null)
                                <div class="mt-1 h-2 rounded-full bg-graphite-100"><div class="h-2 rounded-full {{ $bar((float) $percent, false) }}" style="width: {{ max(2, $percent) }}%"></div></div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! empty($info['storage']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Disques et mémoire</h3>
                <ul class="space-y-2 text-sm">
                    @foreach($info['storage'] as $disk)
                        <li>
                            <div class="flex justify-between"><span>{{ $disk['name'] }}</span><span><span class="font-semibold">{{ number_format($disk['percent'], 0) }} %</span> utilisé · {{ $disk['free'] }} libres sur {{ $disk['total'] }}</span></div>
                            <div class="mt-1 h-2 rounded-full bg-graphite-100"><div class="h-2 rounded-full {{ $bar($disk['percent']) }}" style="width: {{ max(2, $disk['percent']) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! empty($info['cpu']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Processeurs ({{ count($info['cpu']) }} cœurs, moyenne {{ round(array_sum($info['cpu']) / count($info['cpu'])) }} %)</h3>
                <div class="flex h-16 items-end gap-1">
                    @foreach($info['cpu'] as $load)
                        <div class="flex-1 rounded-t {{ $bar((float) $load) }}" style="height: {{ max(4, $load) }}%" title="{{ $load }} %"></div>
                    @endforeach
                </div>
            </div>
        @endif

        @if(! empty($info['interfaces']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Interfaces réseau</h3>
                <ul class="divide-y divide-graphite-100 text-sm">
                    @foreach(array_slice($info['interfaces'], 0, 24) as $interface)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <span class="flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full {{ $interface['up'] ? 'bg-green-600' : 'bg-graphite-300' }}" title="{{ $interface['up'] ? 'Connectée' : 'Déconnectée' }}"></span>{{ $interface['name'] }}</span>
                            <span class="text-graphite-500">{{ $interface['up'] ? ($interface['speed'] ?? 'connectée') : 'déconnectée' }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    <p class="mt-4 text-xs text-graphite-500">Lu en SNMP {{ \Illuminate\Support\Carbon::parse($info['read_at'])->diffForHumans() }} · gardé 10 minutes.</p>
@endif
