{{-- Mettre en pause pour une durée, jusqu'à une date (fenêtre de maintenance) ou sans limite ; ou reprendre. --}}
@if($paused)
    <form method="POST" action="{{ $action }}">
        @csrf
        <button class="bouton-secondaire">Reprendre la supervision</button>
    </form>
@else
    <details class="relative">
        <summary class="bouton-secondaire cursor-pointer list-none">Mettre en pause</summary>
        <form method="POST" action="{{ $action }}" class="fixed inset-x-3 bottom-3 z-50 space-y-3 rounded-xl border border-graphite-200 bg-white p-4 shadow-2xl sm:absolute sm:inset-x-auto sm:bottom-auto sm:right-0 sm:z-30 sm:mt-2 sm:w-80 sm:shadow-xl">
            @csrf
            <label class="block">
                <span class="etiquette">Durée</span>
                <select name="duration" class="champ">
                    @foreach($durations as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="etiquette">Ou jusqu’au (maintenance planifiée)</span>
                <input type="datetime-local" name="until" class="champ">
            </label>
            <label class="block">
                <span class="etiquette">Motif (facultatif)</span>
                <input name="reason" maxlength="255" placeholder="Maintenance électrique" class="champ">
            </label>
            <button class="bouton-primaire w-full">Mettre en pause</button>
        </form>
    </details>
@endif
