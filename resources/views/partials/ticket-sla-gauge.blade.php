<!-- SLA Gauge Widget for Tickets -->
@if($ticket->sla && $ticket->date_echeance_resolution)
    <div class="bg-dark-800 border border-dark-700 rounded-xl p-6" x-data="slaGauge({{ $ticket->id }})">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-white">SLA</h3>
            <div class="flex gap-2">
                @if($ticket->sla_temps_pause_secondes > 0)
                    <form method="POST" action="{{ route('sla.resume-ticket', $ticket) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            ▶️ Reprendre SLA
                        </button>
                    </form>
                @else
                    <button @click="showPauseForm = true" class="px-3 py-1 text-xs bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                        ⏸️ Pause SLA
                    </button>
                @endif
                <button @click="showEscalateForm = true" class="px-3 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    ⬆️ Escalader
                </button>
            </div>
        </div>

        <!-- Progress Gauge -->
        <div class="space-y-3">
            <!-- Resolution Gauge -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <p class="text-sm text-gray-400">Résolution</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span x-text="remainingTime.resolution"></span>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold" x-bind:class="{
                            'text-green-400': slaStatus.resolution === 'ok',
                            'text-yellow-400': slaStatus.resolution === 'warning',
                            'text-red-400': slaStatus.resolution === 'breached'
                        }" x-text="`${percentage.resolution}%`"></p>
                    </div>
                </div>
                <div class="w-full bg-dark-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all" 
                         x-bind:style="`width: ${percentage.resolution}%`"
                         x-bind:class="{
                             'bg-green-500': slaStatus.resolution === 'ok',
                             'bg-yellow-500': slaStatus.resolution === 'warning',
                             'bg-red-500': slaStatus.resolution === 'breached'
                         }"></div>
                </div>
            </div>

            <!-- Response Gauge (if not yet responded) -->
            @if(!$ticket->date_premiere_reponse)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="text-sm text-gray-400">Réponse</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span x-text="remainingTime.response"></span>
                            </p>
                        </div>
                        <p class="text-sm font-bold" x-bind:class="{
                            'text-green-400': slaStatus.response === 'ok',
                            'text-yellow-400': slaStatus.response === 'warning',
                            'text-red-400': slaStatus.response === 'breached'
                        }" x-text="`${percentage.response}%`"></p>
                    </div>
                    <div class="w-full bg-dark-700 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                             x-bind:style="`width: ${percentage.response}%`"
                             x-bind:class="{
                                 'bg-green-500': slaStatus.response === 'ok',
                                 'bg-yellow-500': slaStatus.response === 'warning',
                                 'bg-red-500': slaStatus.response === 'breached'
                             }"></div>
                    </div>
                </div>
            @endif

            <!-- Paused Status -->
            @if($ticket->sla_temps_pause_secondes > 0)
                <div class="mt-3 p-3 bg-yellow-900/20 border border-yellow-800 rounded-lg">
                    <p class="text-xs text-yellow-300">
                        ⏸️ SLA en pause depuis <span x-text="pausedTime"></span> secondes
                    </p>
                </div>
            @endif
        </div>

        <!-- Pause Form Modal -->
        <template x-if="showPauseForm">
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold text-white mb-4">Mettre le SLA en Pause</h3>
                    <form method="POST" action="{{ route('sla.pause-ticket', $ticket) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-white mb-2">
                                Durée (minutes) <span class="text-red-400">*</span>
                            </label>
                            <input type="number" name="duration_minutes" value="30" min="5" max="1440" required
                                class="w-full px-4 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="showPauseForm = false"
                                class="flex-1 px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-700 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition">
                                Mettre en Pause
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Escalate Form Modal -->
        <template x-if="showEscalateForm">
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-dark-800 border border-dark-700 rounded-xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold text-white mb-4">Escalader le Ticket</h3>
                    <form method="POST" action="{{ route('sla.escalate-ticket', $ticket) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-white mb-2">
                                Nouvelle Priorité <span class="text-red-400">*</span>
                            </label>
                            <select name="new_priority_id" required class="w-full px-4 py-2 bg-dark-700 border border-dark-600 rounded-lg text-white focus:border-primary-500 transition">
                                @foreach(\App\Models\TicketPriority::orderByDesc('niveau')->get() as $priority)
                                    @if($priority->id !== $ticket->priorite_id)
                                        <option value="{{ $priority->id }}">{{ $priority->nom }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="showEscalateForm = false"
                                class="flex-1 px-4 py-2 border border-dark-700 text-white rounded-lg hover:bg-dark-700 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                                Escalader
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <script>
    function slaGauge(ticketId) {
        return {
            showPauseForm: false,
            showEscalateForm: false,
            percentage: { response: 0, resolution: 0 },
            slaStatus: { response: 'ok', resolution: 'ok' },
            remainingTime: { response: '—', resolution: '—' },
            pausedTime: {{ $ticket->sla_temps_pause_secondes ?? 0 }},

            async init() {
                await this.updateProgress();
                setInterval(() => this.updateProgress(), 5000); // Update every 5 seconds
            },

            async updateProgress() {
                try {
                    const response = await fetch(`/sla/tickets/${ticketId}/progress`);
                    const data = await response.json();

                    this.percentage.resolution = data.percentage;
                    this.slaStatus.resolution = data.status;
                    this.remainingTime.resolution = data.time_remaining;
                    this.pausedTime = data.paused_seconds;
                } catch (error) {
                    console.error('Failed to update SLA progress:', error);
                }
            }
        };
    }
    </script>
@endif
