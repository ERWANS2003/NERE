@extends('layouts.app')

@section('titre', 'Statistiques')
@section('styles') @include('partials.module-styles')
@media print {
	@page { margin: 1cm; }
	body { background: #fff; color: #111; }
	.sidebar, .entete, .page-actions { display: none !important; }
	.main { margin-left: 0; }
	.contenu { padding: 0; }
	.module-card, .report-card { background: #fff; color: #111; border-color: #bbb; break-inside: avoid; }
	.module-card p, .secondaire { color: #444 !important; }
	.report-chart { height: 220px; }
	a { color: #111; }
}
@endsection

@section('contenu')
<div class="page-actions"><p style="margin:0;color:var(--texte-att);font-size:.88rem;">Vue synthétique de la performance du support</p><div style="display:flex;gap:.5rem;flex-wrap:wrap;"><button class="btn btn-primaire" type="button" onclick="window.print()">Imprimer</button><a class="btn btn-secondaire" href="{{ route('reports.export.csv') }}">Télécharger CSV</a></div></div>
<div class="module-grid"><div class="module-card"><p>Temps moyen de résolution</p><div class="metric">{{ $tempsMoyenResolution !== null ? number_format($tempsMoyenResolution, 1).' h' : '—' }}</div></div><div class="module-card"><p>Satisfaction moyenne</p><div class="metric">{{ $tauxSatisfaction?->moyenne !== null ? number_format($tauxSatisfaction->moyenne, 1).'/5' : '—' }}</div></div><div class="module-card"><p>Tickets dans les délais</p><div class="metric">{{ number_format($respectSla['dans_les_delais']) }}</div></div><div class="module-card"><p>SLA dépassés</p><div class="metric" style="color:#f0a0a0;">{{ number_format($respectSla['sla_depasse']) }}</div></div></div>
<div class="report-grid"><div class="report-card"><h2>Tickets par mois</h2><div class="report-chart"><canvas id="report-month"></canvas></div></div><div class="report-card"><h2>Tickets par site</h2><div class="report-chart"><canvas id="report-site"></canvas></div></div><div class="report-card"><h2>Tickets par catégorie</h2><div class="report-chart"><canvas id="report-category"></canvas></div></div><div class="report-card"><h2>Performance techniciens</h2><div class="table-wrap"><table class="tickets"><thead><tr><th>Technicien</th><th>Traités</th><th>Moyenne</th></tr></thead><tbody>@forelse($performanceTechniciens as $technicien)<tr><td>{{ $technicien->technicien }}</td><td>{{ $technicien->total_traites }}</td><td>{{ $technicien->temps_moyen_heures !== null ? number_format($technicien->temps_moyen_heures, 1).' h' : '—' }}</td></tr>@empty<tr><td colspan="3">Aucune donnée disponible.</td></tr>@endforelse</tbody></table></div></div></div>
<div class="module-card" style="margin-top:1.25rem;"><h2>Historique récent</h2><div class="table-wrap"><table class="tickets"><thead><tr><th>Date</th><th>Ticket</th><th>Action</th><th>Utilisateur</th><th>Détail</th></tr></thead><tbody>@forelse($historiques as $historique)<tr><td style="white-space:nowrap;color:var(--texte-att);">{{ $historique->created_at->format('d/m/Y H:i') }}</td><td><a class="ref" href="{{ route('tickets.show', $historique->ticket) }}">{{ $historique->ticket?->reference ?? '—' }}</a></td><td>{{ $historique->action }}</td><td>{{ $historique->utilisateur?->name ?? 'Système' }}</td><td>{{ $historique->ancienne_valeur ?? '—' }} → {{ $historique->nouvelle_valeur ?? '—' }}</td></tr>@empty<tr><td colspan="5">Aucun historique.</td></tr>@endforelse</tbody></table></div></div>
@endsection

@push('scripts')<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script><script>
const reportPalette=['#e0a52f','#ffc247','#d9362e','#8f2020','#f0c96a','#b94b35'];
function reportChart(id,type,labels,data,color){new Chart(document.getElementById(id),{type,data:{labels,datasets:[{data,backgroundColor:color || reportPalette,borderColor:'#e0a52f',borderWidth:type==='line'?2:0,borderRadius:6,fill:type==='line',tension:.3}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:type==='doughnut',position:'bottom'}},scales:type==='doughnut'?{}:{x:{grid:{display:false}},y:{beginAtZero:true}}}})}
reportChart('report-month','line',@json($ticketsParMois->pluck('periode')),@json($ticketsParMois->pluck('total')),'rgba(224,165,47,.22)');reportChart('report-site','bar',@json($ticketsParSite->pluck('site')),@json($ticketsParSite->pluck('total')));reportChart('report-category','doughnut',@json($ticketsParCategorie->pluck('categorie')),@json($ticketsParCategorie->pluck('total')));
</script>@endpush
