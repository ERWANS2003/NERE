@props(['data' => null])

<div class="widget-body">
    <div class="grid grid-cols-2 gap-3">
        <!-- Créer Ticket -->
        <a href="{{ route('tickets.create') }}" 
           class="quick-action-card bg-gradient-to-br from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700">
            <x-icon name="plus" size="lg" class="text-white" />
            <span class="text-white font-semibold mt-2">Nouveau Ticket</span>
        </a>

        <!-- Mes Tickets -->
        <a href="{{ route('tickets.index') }}?assigned_to={{ auth()->id() }}" 
           class="quick-action-card bg-gradient-to-br from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700">
            <x-icon name="tickets" size="lg" class="text-white" />
            <span class="text-white font-semibold mt-2">Mes Tickets</span>
        </a>

        <!-- Base Connaissances -->
        <a href="{{ route('knowledge.index') }}" 
           class="quick-action-card bg-gradient-to-br from-green-500 to-green-600 hover:from-green-600 hover:to-green-700">
            <x-icon name="book" size="lg" class="text-white" />
            <span class="text-white font-semibold mt-2">Documentation</span>
        </a>

        <!-- Rapports -->
        @can('reports.view')
        <a href="{{ route('reports.index') }}" 
           class="quick-action-card bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700">
            <x-icon name="chart-bar" size="lg" class="text-white" />
            <span class="text-white font-semibold mt-2">Rapports</span>
        </a>
        @else
        <a href="{{ route('assets.index') }}" 
           class="quick-action-card bg-gradient-to-br from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700">
            <x-icon name="assets" size="lg" class="text-white" />
            <span class="text-white font-semibold mt-2">Assets</span>
        </a>
        @endcan
    </div>

    <!-- Stats rapides -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="grid grid-cols-2 gap-3 text-center">
            <div>
                <div class="text-2xl font-bold text-gray-900">
                    {{ \App\Models\Ticket::where('assigned_to', auth()->id())->whereHas('status', fn($q) => $q->where('slug', 'open'))->count() }}
                </div>
                <div class="text-xs text-gray-600">Tickets ouverts</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900">
                    {{ \App\Models\Ticket::where('assigned_to', auth()->id())->whereDate('created_at', today())->count() }}
                </div>
                <div class="text-xs text-gray-600">Aujourd'hui</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Plain CSS on purpose: Blade does not run Tailwind, so an `@apply` inside a
   blade <style> block is shipped verbatim and the browser drops the rule. */
.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100px;
    padding: 1rem;
    border-radius: 0.5rem;
    box-shadow: var(--shadow-sm, 0 1px 2px rgb(0 0 0 / 0.06));
    cursor: pointer;
    transition: all 0.18s ease;
}
.quick-action-card:hover {
    box-shadow: var(--shadow-md, 0 4px 6px -1px rgb(0 0 0 / 0.1));
}
</style>
