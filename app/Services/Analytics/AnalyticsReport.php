<?php

namespace App\Services\Analytics;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\FormSubmission;
use App\Models\KnowledgeArticle;
use App\Models\MenuCampaign;
use App\Models\Resource;
use App\Models\Ticket;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calcule les indicateurs détaillés de la page Analytics.
 *
 * Toutes les agrégations temporelles sont faites en PHP sur des colonnes
 * déjà filtrées par période : le volume d'un intranet le permet, et cela
 * garde le même comportement sous PostgreSQL (production) et SQLite (tests).
 */
class AnalyticsReport
{
    private const JOURS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    private const MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    private readonly Carbon $end;

    private readonly Carbon $previousStart;

    /**
     * @param  list<int>|null  $departmentIds  Null : toute l'entreprise.
     */
    public function __construct(
        private readonly ?array $departmentIds,
        private readonly Carbon $start,
        private readonly int $days,
        private readonly bool $isAdmin,
    ) {
        $this->end = Carbon::now();
        $this->previousStart = (clone $start)->subDays($days);
    }

    /**
     * @return array<string, mixed>
     */
    public function sections(): array
    {
        $sections = [
            'demandes' => $this->requests(),
            'conges' => $this->timeOff(),
            'effectifs' => $this->workforce(),
        ];

        if ($this->showsTickets()) {
            $sections['support'] = $this->tickets();
        }

        if ($this->isAdmin) {
            $sections['contenus'] = $this->content();
        }

        $sections['synthese'] = $this->summary($sections);

        return $sections;
    }

    // ------------------------------------------------------------------ demandes

    /**
     * @return array<string, mixed>
     */
    private function requests(): array
    {
        $current = $this->submissions()
            ->where('created_at', '>=', $this->start)
            ->with(['form:id,name,department_id', 'form.department:id,name', 'reviewer:id,name'])
            ->get(['id', 'form_id', 'status', 'created_at', 'reviewed_at', 'reviewed_by']);
        $previous = $this->submissions()
            ->whereBetween('created_at', [$this->previousStart, $this->start])
            ->get(['id', 'status', 'created_at', 'reviewed_at']);
        $pending = $this->submissions()
            ->where('status', 'pending')
            ->get(['id', 'created_at']);

        $delays = $this->processingHours($current);
        $previousDelays = $this->processingHours($previous);
        $decided = $current->whereIn('status', ['approved', 'rejected']);
        $approvalRate = $decided->count() > 0
            ? round($decided->where('status', 'approved')->count() / $decided->count() * 100)
            : null;

        return [
            'kpis' => [
                $this->kpi('Demandes reçues', $current->count(), $previous->count(), 'count'),
                $this->kpi('Délai médian de traitement', $this->median($delays), $this->median($previousDelays), 'hours', lowerIsBetter: true),
                $this->kpi('Délai 90e centile', $this->percentile($delays, 90), $this->percentile($previousDelays, 90), 'hours', lowerIsBetter: true),
                $this->kpi('Taux d’approbation', $approvalRate, null, 'percent'),
                $this->kpi('En attente (stock)', $pending->count(), null, 'count'),
                $this->kpi('En attente depuis plus de 7 j', $pending->filter(fn ($s) => $s->created_at->lt(now()->subDays(7)))->count(), null, 'count', lowerIsBetter: true),
            ],
            'tendance' => $this->trend($current->pluck('created_at'), $previous->pluck('created_at')),
            'statutsParPeriode' => $this->stackedByBucket($current, fn ($s) => $s->status, [
                'approved' => 'Approuvées',
                'rejected' => 'Rejetées',
                'pending' => 'En attente',
            ]),
            'anciennete' => $this->ageBuckets($pending->pluck('created_at')),
            'heatmap' => $this->heatmap($current->pluck('created_at')),
            'formulaires' => $this->topForms($current),
            'agents' => $this->topReviewers($current),
        ];
    }

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     * @return list<array<string, mixed>>
     */
    private function topForms(Collection $submissions): array
    {
        return $submissions
            ->groupBy('form_id')
            ->map(function (Collection $items) {
                $decided = $items->whereIn('status', ['approved', 'rejected']);
                $form = $items->first()->form;

                return [
                    'nom' => $form?->name ?? 'Formulaire supprimé',
                    'departement' => $form?->department?->name ?? '—',
                    'volume' => $items->count(),
                    'approbation' => $decided->count() > 0
                        ? (int) round($decided->where('status', 'approved')->count() / $decided->count() * 100)
                        : null,
                    'delaiMedian' => $this->median($this->processingHours($items)),
                    'enAttente' => $items->where('status', 'pending')->count(),
                ];
            })
            ->sortByDesc('volume')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     * @return list<array<string, mixed>>
     */
    private function topReviewers(Collection $submissions): array
    {
        return $submissions
            ->filter(fn ($s) => $s->reviewed_by !== null && $s->reviewed_at !== null)
            ->groupBy('reviewed_by')
            ->map(fn (Collection $items) => [
                'nom' => $items->first()->reviewer?->name ?? 'Utilisateur supprimé',
                'traitees' => $items->count(),
                'approuvees' => $items->where('status', 'approved')->count(),
                'delaiMedian' => $this->median($this->processingHours($items)),
            ])
            ->sortByDesc('traitees')
            ->take(8)
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------ support

    private function showsTickets(): bool
    {
        return $this->isAdmin || $this->ticketQuery()->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function tickets(): array
    {
        $columns = ['id', 'category', 'priority', 'status', 'assignee_id', 'created_at', 'first_responded_at',
            'first_response_due_at', 'response_breached_at', 'resolved_at', 'resolution_due_at', 'resolution_breached_at'];

        $created = $this->ticketQuery()->where('created_at', '>=', $this->start)->with('assignee:id,name')->get($columns);
        $previousCreated = $this->ticketQuery()->whereBetween('created_at', [$this->previousStart, $this->start])->get($columns);
        $resolved = $this->ticketQuery()->where('resolved_at', '>=', $this->start)->get($columns);
        $open = $this->ticketQuery()
            ->whereNotIn('status', [TicketStatus::Resolved->value, TicketStatus::Closed->value])
            ->with('assignee:id,name')
            ->get($columns);

        $responseSla = $this->slaCompliance($created, 'first_responded_at', 'first_response_due_at', 'response_breached_at');
        $resolutionSla = $this->slaCompliance($created, 'resolved_at', 'resolution_due_at', 'resolution_breached_at');
        $previousResolutionSla = $this->slaCompliance($previousCreated, 'resolved_at', 'resolution_due_at', 'resolution_breached_at');
        $firstResponse = $this->hoursBetween($created, 'created_at', 'first_responded_at');
        $previousFirstResponse = $this->hoursBetween($previousCreated, 'created_at', 'first_responded_at');
        $mttr = $this->hoursBetween($resolved, 'created_at', 'resolved_at');

        $priorities = collect(TicketPriority::cases());

        return [
            'kpis' => [
                $this->kpi('Tickets créés', $created->count(), $previousCreated->count(), 'count'),
                $this->kpi('Tickets résolus', $resolved->count(), null, 'count'),
                $this->kpi('Tickets ouverts', $open->count(), null, 'count'),
                $this->kpi('SLA 1re réponse respecté', $responseSla['taux'], null, 'percent'),
                $this->kpi('SLA résolution respecté', $resolutionSla['taux'], $previousResolutionSla['taux'], 'percent'),
                $this->kpi('1re réponse médiane', $this->median($firstResponse), $this->median($previousFirstResponse), 'hours', lowerIsBetter: true),
                $this->kpi('Résolution médiane (MTTR)', $this->median($mttr), null, 'hours', lowerIsBetter: true),
                $this->kpi('SLA dépassés (période)', $responseSla['depasses'] + $resolutionSla['depasses'], null, 'count', lowerIsBetter: true),
            ],
            'flux' => $this->multiTrend([
                'Créés' => $created->pluck('created_at'),
                'Résolus' => $resolved->pluck('resolved_at'),
            ]),
            'parCategorie' => $this->countBy($created, fn ($t) => $t->category?->value, collect(TicketCategory::cases())
                ->mapWithKeys(fn (TicketCategory $c) => [$c->value => $c->label()])->all()),
            'ouvertsParPriorite' => $this->countBy($open, fn ($t) => $t->priority?->value, $priorities
                ->reverse()->mapWithKeys(fn (TicketPriority $p) => [$p->value => $p->label()])->all()),
            'sla' => [
                'labels' => ['1re réponse', 'Résolution'],
                'respectes' => [$responseSla['respectes'], $resolutionSla['respectes']],
                'depasses' => [$responseSla['depasses'], $resolutionSla['depasses']],
            ],
            'anciennete' => $this->ageBuckets($open->pluck('created_at')),
            'techniciens' => $this->technicianWorkload($open, $resolved),
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $tickets
     * @return array{respectes: int, depasses: int, taux: int|null}
     */
    private function slaCompliance(Collection $tickets, string $doneAt, string $dueAt, string $breachedAt): array
    {
        $met = 0;
        $missed = 0;

        foreach ($tickets as $ticket) {
            if ($ticket->{$dueAt} === null) {
                continue;
            }

            if ($ticket->{$breachedAt} !== null || ($ticket->{$doneAt} !== null && $ticket->{$doneAt}->gt($ticket->{$dueAt}))
                || ($ticket->{$doneAt} === null && $ticket->{$dueAt}->isPast())) {
                $missed++;
            } elseif ($ticket->{$doneAt} !== null) {
                $met++;
            }
        }

        $total = $met + $missed;

        return ['respectes' => $met, 'depasses' => $missed, 'taux' => $total > 0 ? (int) round($met / $total * 100) : null];
    }

    /**
     * @param  Collection<int, Ticket>  $open
     * @param  Collection<int, Ticket>  $resolved
     * @return list<array<string, mixed>>
     */
    private function technicianWorkload(Collection $open, Collection $resolved): array
    {
        $names = User::whereIn('id', $open->pluck('assignee_id')->merge($resolved->pluck('assignee_id'))->filter()->unique())
            ->pluck('name', 'id');

        return $names
            ->map(fn (string $name, int $id) => [
                'nom' => $name,
                'ouverts' => $open->where('assignee_id', $id)->count(),
                'urgents' => $open->where('assignee_id', $id)
                    ->filter(fn ($t) => in_array($t->priority, [TicketPriority::Urgent, TicketPriority::High], true))->count(),
                'resolus' => $resolved->where('assignee_id', $id)->count(),
                'mttr' => $this->median($this->hoursBetween($resolved->where('assignee_id', $id), 'created_at', 'resolved_at')),
            ])
            ->push([
                'nom' => 'Non assignés',
                'ouverts' => $open->whereNull('assignee_id')->count(),
                'urgents' => $open->whereNull('assignee_id')
                    ->filter(fn ($t) => in_array($t->priority, [TicketPriority::Urgent, TicketPriority::High], true))->count(),
                'resolus' => 0,
                'mttr' => null,
            ])
            ->filter(fn ($row) => $row['ouverts'] + $row['resolus'] > 0)
            ->sortByDesc('ouverts')
            ->values()
            ->all();
    }

    /**
     * @return Builder<Ticket>
     */
    private function ticketQuery(): Builder
    {
        return Ticket::query()->when(
            $this->departmentIds !== null,
            fn (Builder $query) => $query->whereIn('department_id', $this->departmentIds)
        );
    }

    // ------------------------------------------------------------------ congés

    /**
     * @return array<string, mixed>
     */
    private function timeOff(): array
    {
        $base = fn () => TimeOffRequest::query()->when(
            $this->departmentIds !== null,
            fn (Builder $query) => $query->whereHas('user.departments', fn (Builder $d) => $d->whereIn('departments.id', $this->departmentIds))
        );

        $requests = $base()->where('created_at', '>=', $this->start)->with('policy:id,name')
            ->get(['id', 'policy_id', 'user_id', 'status', 'total_days', 'created_at', 'approved_at']);
        $previous = $base()->whereBetween('created_at', [$this->previousStart, $this->start])
            ->get(['id', 'status', 'total_days', 'created_at', 'approved_at']);
        $today = now()->startOfDay();
        $absentToday = $base()->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)
            ->distinct()->count('user_id');
        $upcoming = $base()->where('status', 'approved')
            ->whereDate('start_date', '<=', $today->copy()->addDays(14))->whereDate('end_date', '>=', $today)
            ->with('user:id,name', 'policy:id,name')
            ->orderBy('start_date')
            ->get(['id', 'user_id', 'policy_id', 'start_date', 'end_date', 'total_days']);

        $approvedDays = (float) $requests->where('status', 'approved')->sum('total_days');
        $previousApprovedDays = (float) $previous->where('status', 'approved')->sum('total_days');
        $decided = $requests->whereIn('status', ['approved', 'denied']);
        $decisionHours = $this->hoursBetween($decided, 'created_at', 'approved_at');

        return [
            'kpis' => [
                $this->kpi('Demandes de congé', $requests->count(), $previous->count(), 'count'),
                $this->kpi('Jours approuvés', $approvedDays, $previousApprovedDays, 'days'),
                $this->kpi('Taux d’approbation', $decided->count() > 0
                    ? round($decided->where('status', 'approved')->count() / $decided->count() * 100) : null, null, 'percent'),
                $this->kpi('Délai médian de décision', $this->median($decisionHours), null, 'hours', lowerIsBetter: true),
                $this->kpi('Absents aujourd’hui', $absentToday, null, 'count'),
                $this->kpi('En attente de décision', $base()->where('status', 'pending')->count(), null, 'count', lowerIsBetter: true),
            ],
            'tendance' => $this->trend($requests->pluck('created_at'), $previous->pluck('created_at')),
            'joursParType' => $requests->where('status', 'approved')
                ->groupBy(fn ($r) => $r->policy?->name ?? 'Autre')
                ->map(fn (Collection $items) => (float) $items->sum('total_days'))
                ->sortDesc()
                ->pipe(fn (Collection $c) => ['labels' => $c->keys()->all(), 'data' => $c->values()->all()]),
            'aVenir' => $upcoming->map(fn ($r) => [
                'nom' => $r->user?->name ?? '—',
                'type' => $r->policy?->name ?? '—',
                'du' => $r->start_date->format('d/m'),
                'au' => $r->end_date->format('d/m'),
                'jours' => (float) $r->total_days,
            ])->take(12)->values()->all(),
        ];
    }

    // ------------------------------------------------------------------ contenus

    /**
     * @return array<string, mixed>
     */
    private function content(): array
    {
        $feedback = DB::table('knowledge_article_feedback')->where('created_at', '>=', $this->start)->get(['knowledge_article_id', 'helpful']);
        $helpfulRate = $feedback->count() > 0 ? round($feedback->where('helpful', true)->count() / $feedback->count() * 100) : null;

        $articles = KnowledgeArticle::query()
            ->withCount([
                'feedback as utile_count' => fn ($q) => $q->where('knowledge_article_feedback.helpful', true),
                'feedback as inutile_count' => fn ($q) => $q->where('knowledge_article_feedback.helpful', false),
            ])
            ->get(['id', 'title', 'slug', 'is_published']);

        $activeUsers = User::where('actif', true)->count();
        $tutorialDone = User::where('actif', true)->whereNotNull('tutorial_completed_at')->count();
        $campaign = MenuCampaign::query()->withCount('registrations')->latest('starts_on')->first();

        return [
            'kpis' => [
                $this->kpi('Articles publiés', $articles->where('is_published', true)->count(), null, 'count'),
                $this->kpi('Avis « utile » (période)', $helpfulRate, null, 'percent'),
                $this->kpi('Téléchargements de ressources', (int) Resource::sum('download_count'), null, 'count'),
                $this->kpi('Annonces publiées', Announcement::where('created_at', '>=', $this->start)->count(), null, 'count'),
                $this->kpi('Tutoriel terminé', $activeUsers > 0 ? round($tutorialDone / $activeUsers * 100) : null, null, 'percent'),
                $this->kpi('Inscrits au menu (dernière campagne)', $campaign?->registrations_count, null, 'count'),
            ],
            'articles' => $articles
                ->filter(fn ($a) => $a->utile_count + $a->inutile_count > 0)
                ->map(fn ($a) => [
                    'titre' => $a->title,
                    'utile' => $a->utile_count,
                    'inutile' => $a->inutile_count,
                    'score' => (int) round($a->utile_count / ($a->utile_count + $a->inutile_count) * 100),
                ])
                ->sortByDesc(fn ($a) => $a['utile'] + $a['inutile'])
                ->take(8)->values()->all(),
            'ressources' => Resource::query()->orderByDesc('download_count')->limit(8)->get(['title', 'download_count', 'file_type'])
                ->map(fn ($r) => ['titre' => $r->title, 'type' => strtoupper((string) $r->file_type), 'telechargements' => (int) $r->download_count])
                ->all(),
            'menu' => $campaign ? [
                'titre' => $campaign->title,
                'inscrits' => $campaign->registrations_count,
                'participation' => $activeUsers > 0 ? (int) round($campaign->registrations_count / $activeUsers * 100) : null,
            ] : null,
        ];
    }

    // ------------------------------------------------------------------ effectifs

    /**
     * @return array<string, mixed>
     */
    private function workforce(): array
    {
        $users = User::query()
            ->when($this->departmentIds !== null, fn (Builder $q) => $q->whereHas('departments', fn (Builder $d) => $d->whereIn('departments.id', $this->departmentIds)))
            ->get(['id', 'actif', 'guid', 'locked_until', 'created_at', 'tutorial_completed_at']);
        $active = $users->where('actif', true);

        $departments = Department::active()
            ->when($this->departmentIds !== null, fn ($q) => $q->whereKey($this->departmentIds))
            ->withCount([
                'users as actifs_count' => fn ($q) => $q->where('actif', true),
                'users as inactifs_count' => fn ($q) => $q->where('actif', false),
            ])
            ->orderBy('position')->orderBy('name')
            ->get(['id', 'name']);

        return [
            'kpis' => [
                $this->kpi('Employés actifs', $active->count(), null, 'count'),
                $this->kpi('Comptes désactivés', $users->where('actif', false)->count(), null, 'count'),
                $this->kpi('Comptes verrouillés', $users->filter(fn ($u) => $u->locked_until !== null && $u->locked_until->isFuture())->count(), null, 'count', lowerIsBetter: true),
                $this->kpi('Comptes Active Directory', $active->whereNotNull('guid')->count(), null, 'count'),
                $this->kpi('Nouveaux comptes (période)', $users->filter(fn ($u) => $u->created_at?->gte($this->start))->count(), null, 'count'),
            ],
            'parDepartement' => [
                'labels' => $departments->pluck('name')->all(),
                'actifs' => $departments->pluck('actifs_count')->map(fn ($v) => (int) $v)->all(),
                'inactifs' => $departments->pluck('inactifs_count')->map(fn ($v) => (int) $v)->all(),
            ],
            'origine' => [
                'labels' => ['Active Directory', 'Comptes locaux'],
                'data' => [$active->whereNotNull('guid')->count(), $active->whereNull('guid')->count()],
            ],
        ];
    }

    // ------------------------------------------------------------------ synthèse

    /**
     * Points d'attention calculés à partir des autres sections.
     *
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    private function summary(array $sections): array
    {
        $alerts = [];
        $find = fn (string $section, string $label) => collect($sections[$section]['kpis'] ?? [])->firstWhere('label', $label);

        $volume = $find('demandes', 'Demandes reçues');
        if ($volume && $volume['variation'] && $volume['variation']['pourcentage'] >= 20) {
            $alerts[] = ['niveau' => 'info', 'texte' => 'Le volume de demandes est en '.$volume['variation']['sens'].' de '.$volume['variation']['pourcentage'].' % par rapport à la période précédente.'];
        }

        $stale = $find('demandes', 'En attente depuis plus de 7 j');
        if ($stale && $stale['brut'] > 0) {
            $alerts[] = ['niveau' => 'alerte', 'texte' => $stale['brut'].' demande(s) attendent une décision depuis plus de 7 jours.'];
        }

        if (isset($sections['support'])) {
            $breaches = $find('support', 'SLA dépassés (période)');
            if ($breaches && $breaches['brut'] > 0) {
                $alerts[] = ['niveau' => 'critique', 'texte' => $breaches['brut'].' engagement(s) SLA du support IT ont été dépassés sur la période.'];
            }

            $unassigned = collect($sections['support']['techniciens'])->firstWhere('nom', 'Non assignés');
            if ($unassigned && $unassigned['ouverts'] > 0) {
                $alerts[] = ['niveau' => 'alerte', 'texte' => $unassigned['ouverts'].' ticket(s) ouvert(s) ne sont assignés à aucun technicien.'];
            }
        }

        $locked = $find('effectifs', 'Comptes verrouillés');
        if ($locked && $locked['brut'] > 0) {
            $alerts[] = ['niveau' => 'alerte', 'texte' => $locked['brut'].' compte(s) utilisateur sont actuellement verrouillés après des échecs de connexion.'];
        }

        $absent = $find('conges', 'Absents aujourd’hui');
        if ($absent && $absent['brut'] > 0) {
            $alerts[] = ['niveau' => 'info', 'texte' => $absent['brut'].' collaborateur(s) sont absents aujourd’hui.'];
        }

        if ($alerts === []) {
            $alerts[] = ['niveau' => 'ok', 'texte' => 'Aucun point d’attention : les indicateurs suivis sont dans les normes.'];
        }

        $headline = array_values(array_filter([
            $volume,
            $find('demandes', 'Délai médian de traitement'),
            $find('demandes', 'En attente (stock)'),
            isset($sections['support']) ? $find('support', 'SLA résolution respecté') : null,
            isset($sections['support']) ? $find('support', 'Tickets ouverts') : null,
            $find('conges', 'Jours approuvés'),
            $find('effectifs', 'Employés actifs'),
            isset($sections['contenus']) ? $find('contenus', 'Tutoriel terminé') : null,
        ]));

        return ['kpis' => $headline, 'alertes' => $alerts];
    }

    // ------------------------------------------------------------------ outils

    /**
     * @param  'count'|'hours'|'percent'|'days'  $unit
     * @return array<string, mixed>
     */
    private function kpi(string $label, int|float|null $value, int|float|null $previous, string $unit, bool $lowerIsBetter = false): array
    {
        $variation = null;
        if ($value !== null && $previous !== null && $previous > 0) {
            $percentage = (int) round(($value - $previous) / $previous * 100);
            $variation = [
                'sens' => $percentage >= 0 ? 'hausse' : 'baisse',
                'pourcentage' => abs($percentage),
                'favorable' => $percentage === 0 ? null : (($percentage < 0) === $lowerIsBetter),
            ];
        }

        return [
            'label' => $label,
            'brut' => $value,
            'valeur' => $this->format($value, $unit),
            'variation' => $variation,
        ];
    }

    private function format(int|float|null $value, string $unit): string
    {
        if ($value === null) {
            return '—';
        }

        return match ($unit) {
            'percent' => round($value).' %',
            'days' => rtrim(rtrim(number_format($value, 1, ',', ' '), '0'), ',').' j',
            'hours' => $value < 1 ? round($value * 60).' min' : ($value < 48 ? round($value, 1).' h' : round($value / 24, 1).' j'),
            default => number_format($value, 0, ',', ' '),
        };
    }

    /**
     * @return Builder<FormSubmission>
     */
    private function submissions(): Builder
    {
        return FormSubmission::query()->when(
            $this->departmentIds !== null,
            fn (Builder $query) => $query->whereHas('form', fn (Builder $forms) => $forms->whereIn('department_id', $this->departmentIds))
        );
    }

    /**
     * @param  Collection<int, FormSubmission>  $submissions
     * @return list<float>
     */
    private function processingHours(Collection $submissions): array
    {
        return $this->hoursBetween($submissions->whereIn('status', ['approved', 'rejected']), 'created_at', 'reviewed_at');
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return list<float>
     */
    private function hoursBetween(Collection $items, string $from, string $to): array
    {
        return $items
            ->filter(fn ($item) => $item->{$from} !== null && $item->{$to} !== null)
            ->map(fn ($item) => max(0, $item->{$from}->diffInMinutes($item->{$to})) / 60)
            ->values()
            ->all();
    }

    /**
     * @param  list<float>  $values
     */
    private function median(array $values): ?float
    {
        return $this->percentile($values, 50);
    }

    /**
     * @param  list<float>  $values
     */
    private function percentile(array $values, int $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $position = ($percentile / 100) * (count($values) - 1);
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        return $values[$lower] + ($values[$upper] - $values[$lower]) * ($position - $lower);
    }

    /**
     * Découpe la période en jours, semaines ou mois selon sa longueur.
     *
     * @return array{keys: list<string>, labels: list<string>, key: callable(Carbon): string}
     */
    private function buckets(Carbon $from): array
    {
        [$step, $key, $label] = match (true) {
            $this->days <= 31 => ['addDay', fn (Carbon $d) => $d->format('Y-m-d'), fn (Carbon $d) => $d->format('d/m')],
            $this->days <= 120 => ['addWeek', fn (Carbon $d) => $d->copy()->startOfWeek()->format('Y-m-d'), fn (Carbon $d) => 'Sem. '.$d->copy()->startOfWeek()->format('d/m')],
            default => ['addMonth', fn (Carbon $d) => $d->format('Y-m'), fn (Carbon $d) => self::MOIS[$d->month - 1].' '.$d->format('y')],
        };

        $keys = [];
        $labels = [];
        $cursor = $from->copy()->startOfDay();
        $end = $from->copy()->addDays($this->days);

        while ($cursor->lte($end)) {
            $k = $key($cursor);
            if (! in_array($k, $keys, true)) {
                $keys[] = $k;
                $labels[] = $label($cursor);
            }
            $cursor->{$step}();
        }

        if (! in_array($key($end), $keys, true)) {
            $keys[] = $key($end);
            $labels[] = $label($end);
        }

        return ['keys' => $keys, 'labels' => $labels, 'key' => $key];
    }

    /**
     * Série de la période et série de la période précédente alignée sur les mêmes rangs.
     *
     * @param  Collection<int, Carbon|null>  $current
     * @param  Collection<int, Carbon|null>  $previous
     * @return array<string, mixed>
     */
    private function trend(Collection $current, Collection $previous): array
    {
        $now = $this->buckets($this->start);
        $before = $this->buckets($this->previousStart);

        return [
            'labels' => $now['labels'],
            'actuelle' => $this->fill($now, $current),
            'precedente' => array_slice(array_pad($this->fill($before, $previous), count($now['keys']), 0), 0, count($now['keys'])),
        ];
    }

    /**
     * @param  array<string, Collection<int, Carbon|null>>  $series
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    private function multiTrend(array $series): array
    {
        $buckets = $this->buckets($this->start);

        return [
            'labels' => $buckets['labels'],
            'series' => array_map(fn (Collection $dates) => $this->fill($buckets, $dates), $series),
        ];
    }

    /**
     * @param  array{keys: list<string>, labels: list<string>, key: callable(Carbon): string}  $buckets
     * @param  Collection<int, Carbon|null>  $dates
     * @return list<int>
     */
    private function fill(array $buckets, Collection $dates): array
    {
        $counts = array_fill_keys($buckets['keys'], 0);

        foreach ($dates->filter() as $date) {
            $k = ($buckets['key'])($date);
            if (array_key_exists($k, $counts)) {
                $counts[$k]++;
            }
        }

        return array_values($counts);
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  array<string, string>  $labels
     * @return array{labels: list<string>, series: array<string, list<int>>}
     */
    private function stackedByBucket(Collection $items, callable $group, array $labels): array
    {
        $buckets = $this->buckets($this->start);
        $series = [];

        foreach ($labels as $value => $label) {
            $series[$label] = $this->fill($buckets, $items->filter(fn ($i) => $group($i) === $value)->pluck('created_at'));
        }

        return ['labels' => $buckets['labels'], 'series' => $series];
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  array<string, string>  $labels
     * @return array{labels: list<string>, data: list<int>}
     */
    private function countBy(Collection $items, callable $group, array $labels): array
    {
        $counts = $items->countBy(fn ($i) => $group($i));

        return [
            'labels' => array_values($labels),
            'data' => array_map(fn ($value) => (int) ($counts[$value] ?? 0), array_keys($labels)),
        ];
    }

    /**
     * @param  Collection<int, Carbon|null>  $dates
     * @return array{labels: list<string>, data: list<int>}
     */
    private function ageBuckets(Collection $dates): array
    {
        $buckets = ['< 24 h' => 1, '1 à 3 j' => 3, '3 à 7 j' => 7, '7 à 30 j' => 30, '> 30 j' => PHP_INT_MAX];
        $data = array_fill_keys(array_keys($buckets), 0);

        foreach ($dates->filter() as $date) {
            $age = $date->diffInHours(now()) / 24;
            foreach ($buckets as $label => $max) {
                if ($age < $max) {
                    $data[$label]++;
                    break;
                }
            }
        }

        return ['labels' => array_keys($data), 'data' => array_values($data)];
    }

    /**
     * Répartition jour de semaine × heure, pour repérer les pics d'activité.
     *
     * @param  Collection<int, Carbon|null>  $dates
     * @return array{jours: list<string>, heures: list<int>, cellules: list<list<int>>, max: int}
     */
    private function heatmap(Collection $dates): array
    {
        $cells = array_fill(0, 7, array_fill(0, 24, 0));

        foreach ($dates->filter() as $date) {
            $local = $date->copy()->setTimezone(config('app.timezone'));
            $cells[$local->dayOfWeekIso - 1][$local->hour]++;
        }

        return [
            'jours' => self::JOURS,
            'heures' => range(0, 23),
            'cellules' => $cells,
            'max' => max(1, max(array_map('max', $cells))),
        ];
    }
}
