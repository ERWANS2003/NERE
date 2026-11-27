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
    $fields = $device->type->sheetFields();
    $details = $device->details ?? [];
    $snmpIdentity = $info['identity'] ?? [];
    // Ce que SNMP sait déjà remplit la fiche quand rien n'a été saisi.
    $fromSnmp = array_filter(['model' => $snmpIdentity['Modèle'] ?? null, 'serial' => $snmpIdentity['N° de série'] ?? null, 'location' => $snmpIdentity['Emplacement'] ?? null]);
    $counted = collect($fields)->except('notes');
    $filled = $counted->keys()->filter(fn ($key) => filled($details[$key] ?? null) || isset($fromSnmp[$key]))->count();
    $completion = (int) round(100 * $filled / max(1, $counted->count()));
    $warranty = filled($details['warranty_until'] ?? null) ? \Illuminate\Support\Carbon::parse($details['warranty_until'])->startOfDay() : null;
    $network = $info['network'] ?? null;
    $snmpGives = match ($device->type) {
        \App\Enums\MonitorDeviceType::Printer => 'les niveaux de toner, le papier, le compteur de pages, l’écran et les alertes',
        \App\Enums\MonitorDeviceType::Server => 'les disques, la mémoire, les processeurs et les interfaces',
        \App\Enums\MonitorDeviceType::Camera => 'le modèle exact, la durée de marche et les interfaces',
        default => 'le modèle exact, les interfaces, leur débit et les équipements voisins',
    };
    $heading = 'mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500';
@endphp

<div class="mb-6 grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-graphite-500">{{ $device->type->label() }} : fiche</h3>
            <div class="flex items-center gap-3 text-sm">
                <span class="flex items-center gap-2 text-graphite-600" title="Champs renseignés">
                    <span class="h-2 w-24 rounded-full bg-graphite-100"><span class="block h-2 rounded-full {{ $completion >= 80 ? 'bg-green-600' : ($completion >= 40 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ max(4, $completion) }}%"></span></span>
                    {{ $filled }} / {{ $counted->count() }}
                </span>
                <a href="{{ route('intranet.monitor.devices.edit', $device) }}#fiche" class="font-medium text-nere-700 hover:underline">{{ $completion < 100 ? 'Compléter la fiche' : 'Modifier la fiche' }}</a>
            </div>
        </div>
        <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
            @foreach($counted as $key => $field)
                @php
                    $value = $details[$key] ?? null;
                    if (filled($value) && ($field['input'] ?? null) === 'date') {
                        $value = \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
                    }
                @endphp
                <div class="min-w-0">
                    <dt class="text-xs text-graphite-500">{{ $field['label'] }}</dt>
                    <dd class="break-words {{ filled($value) || isset($fromSnmp[$key]) ? 'font-medium text-graphite-900' : 'text-graphite-400' }}">
                        @if(filled($value))
                            {{ $value }}
                            @if($key === 'warranty_until')
                                @if($warranty->isPast())
                                    <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">expirée</span>
                                @elseif($warranty->lte(now()->addDays(90)))
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">expire dans {{ (int) now()->startOfDay()->diffInDays($warranty) }} j</span>
                                @else
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800">sous garantie</span>
                                @endif
                            @endif
                        @elseif(isset($fromSnmp[$key]))
                            {{ $fromSnmp[$key] }} <span class="text-xs font-normal text-graphite-400">(lu en SNMP)</span>
                        @else
                            Non renseigné
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
        @if(filled($details['notes'] ?? null))
            <div class="mt-3 rounded-lg bg-graphite-50 p-3 text-sm text-graphite-700">
                <p class="text-xs text-graphite-500">Remarques</p>
                <p class="whitespace-pre-line">{{ $details['notes'] }}</p>
            </div>
        @endif
    </div>

    <div>
        <h3 class="{{ $heading }}">Réseau</h3>
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
            <dt class="text-graphite-500">Adresse</dt>
            <dd class="font-mono font-medium text-graphite-900">{{ $device->host }}</dd>
            <dt class="text-graphite-500">Nom DNS</dt>
            <dd class="break-all {{ $network['name'] ?? null ? 'font-medium text-graphite-900' : 'text-graphite-400' }}">{{ $network['name'] ?? 'Inconnu' }}</dd>
            <dt class="text-graphite-500">Adresse MAC</dt>
            <dd class="{{ $network['mac'] ?? null ? 'font-mono font-medium text-graphite-900' : 'text-graphite-400' }}">{{ $network['mac'] ?? 'Inconnue' }}</dd>
            @if($network['web'] ?? null)
                <dt class="text-graphite-500">Interface web</dt>
                <dd class="min-w-0"><a href="{{ $network['web']['url'] }}" target="_blank" rel="noopener" class="break-words font-medium text-nere-700 hover:underline">{{ $network['web']['title'] ?? $network['web']['url'] }}</a></dd>
            @endif
        </dl>
        <p class="mt-3 text-xs text-graphite-500">Services ouverts</p>
        @if(! empty($network['ports']))
            <ul class="mt-1 flex flex-wrap gap-1.5 text-xs">
                @foreach($network['ports'] as $port => $label)
                    <li class="rounded-md bg-green-50 px-2 py-1 text-green-800"><span class="font-mono font-semibold">{{ $port }}</span> {{ $label }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-graphite-400">Aucun des ports habituels pour ce type d’équipement ne répond.</p>
        @endif
    </div>
</div>

@if(isset($info['snmp_error']))
    <p class="rounded-lg bg-graphite-50 p-3 text-sm text-graphite-600">
        <span class="font-semibold text-graphite-800">SNMP ne répond pas</span> ({{ $info['snmp_error'] }}).
        Activez-le sur l’équipement (communauté « public » par défaut, ou celle des réglages du groupe) pour voir aussi {{ $snmpGives }}.
    </p>
@else
    <div class="grid gap-6 border-t border-graphite-100 pt-6 lg:grid-cols-2">
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Lu en SNMP</h3>
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5 text-sm">
                @foreach($info['identity'] as $label => $value)
                    <dt class="text-graphite-500">{{ $label }}</dt>
                    <dd class="font-medium text-graphite-900">{{ $value }}</dd>
                @endforeach
            </dl>
        </div>

        @if(! empty($info['display']) || isset($info['pages']) || ! empty($info['alerts']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Imprimante</h3>
                @if(! empty($info['display']))
                    <p class="rounded-lg bg-graphite-900 px-4 py-3 font-mono text-sm text-green-300" title="Texte affiché sur l’écran de l’imprimante">{{ implode(' · ', $info['display']) }}</p>
                @endif
                @if(isset($info['pages']))
                    <p class="mt-3 text-sm text-graphite-600">Compteur total : <span class="text-lg font-semibold text-graphite-900">{{ number_format($info['pages'], 0, ',', ' ') }}</span> pages</p>
                @endif
                @if(! empty($info['alerts']))
                    <p class="mt-3 text-xs text-graphite-500">Alertes en cours</p>
                    <ul class="mt-1 space-y-1 text-sm">
                        @foreach($info['alerts'] as $alert)
                            <li class="rounded-md bg-amber-50 px-2 py-1 text-amber-900">{{ $alert }}</li>
                        @endforeach
                    </ul>
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

        @if(! empty($info['neighbors']))
            <div>
                <h3 class="{{ $heading }}">Équipements voisins (LLDP)</h3>
                <ul class="divide-y divide-graphite-100 text-sm">
                    @foreach($info['neighbors'] as $neighbor)
                        <li class="flex justify-between gap-3 py-1.5"><span class="font-medium text-graphite-900">{{ $neighbor['name'] }}</span><span class="text-graphite-500">{{ $neighbor['port'] }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(! empty($info['interfaces']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-graphite-500">Interfaces réseau</h3>
                <ul class="divide-y divide-graphite-100 text-sm">
                    @foreach(array_slice($info['interfaces'], 0, 24) as $interface)
                        <li class="flex items-center justify-between gap-3 py-1.5">
                            <span class="flex min-w-0 items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $interface['up'] ? 'bg-green-600' : 'bg-graphite-300' }}" title="{{ $interface['up'] ? 'Connectée' : 'Déconnectée' }}"></span><span class="truncate">{{ $interface['name'] }}</span>@if($interface['mac'] ?? null)<span class="hidden font-mono text-xs text-graphite-400 sm:inline">{{ $interface['mac'] }}</span>@endif</span>
                            <span class="flex items-center gap-3 text-graphite-500">
                                {{ $interface['up'] ? ($interface['speed'] ?? 'connectée') : 'déconnectée' }}
                                @if(isset($interface['index']) && ! in_array((string) $interface['index'], $watchedPorts ?? [], true))
                                    <form method="POST" action="{{ route('intranet.monitor.sensors.store', $device) }}">
                                        @csrf
                                        <input type="hidden" name="quick" value="1">
                                        <input type="hidden" name="kind" value="snmp_traffic">
                                        <input type="hidden" name="name" value="Trafic {{ $interface['name'] }}">
                                        <input type="hidden" name="parameter" value="{{ $interface['index'] }}">
                                        <button class="text-xs font-medium text-nere-700 hover:underline">Surveiller le trafic</button>
                                    </form>
                                @elseif(isset($interface['index']))
                                    <span class="text-xs text-green-700">surveillé</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
<p class="mt-4 text-xs text-graphite-500">Relevé {{ \Illuminate\Support\Carbon::parse($info['read_at'])->diffForHumans() }} · gardé 10 minutes.</p>
