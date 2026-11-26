{{-- Bouton « Je m'en occupe » : la note et la durée s'ouvrent dans une petite fenêtre. --}}
<details class="relative">
    <summary class="bouton-secondaire cursor-pointer list-none whitespace-nowrap">Je m’en occupe</summary>
    <form method="POST" action="{{ route('intranet.monitor.sensors.acknowledge', $sensor) }}" class="absolute right-0 z-30 mt-2 w-80 space-y-3 rounded-xl border border-graphite-200 bg-white p-4 shadow-xl">
        @csrf
        <p class="text-sm text-graphite-700">L’équipe verra que vous prenez cette panne en charge. Les rappels et l’escalade s’arrêtent.</p>
        <label class="block">
            <span class="etiquette">Note (facultatif)</span>
            <input name="acknowledgement" maxlength="255" placeholder="Ex. technicien en route" class="champ">
        </label>
        <label class="block">
            <span class="etiquette">Pendant</span>
            <select name="duration" class="champ">
                @foreach(\App\Http\Controllers\Intranet\MonitorController::DURATIONS as $value => $label)
                    <option value="{{ $value }}" @selected((string) $value === '0')>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="bouton-primaire w-full">Confirmer</button>
    </form>
</details>
