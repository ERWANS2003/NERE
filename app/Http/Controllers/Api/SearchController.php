<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * Index de recherche global consommé par le modal Cmd+K.
     *
     * Renvoie l'ensemble des entités consultables sous une forme homogène :
     * le filtrage fuzzy est fait côté client par Fuse.js. Volume raisonnable
     * pour un intranet (quelques centaines d'entrées), chargé une seule fois
     * à l'ouverture de la page.
     *
     * @return JsonResponse<list<array{id:string,type:string,title:string,description:string,url:string,tags:string}>>
     */
    public function data(Request $request): JsonResponse
    {
        $resultats = collect();

        // Employés actifs
        User::where('actif', true)
            ->with('departments')
            ->get()
            ->each(function (User $u) use ($resultats) {
                $resultats->push([
                    'id' => 'employee-'.$u->id,
                    'type' => 'employee',
                    'title' => $u->name,
                    'description' => trim(($u->job_title ?? '').' · '.$u->departments->pluck('name')->implode(', '), ' ·'),
                    'url' => route('intranet.directory', ['search' => $u->name]),
                    'tags' => $u->email.' '.$u->departments->pluck('code')->implode(' '),
                ]);
            });

        // Départements
        Department::active()->get()->each(function (Department $d) use ($resultats) {
            $resultats->push([
                'id' => 'department-'.$d->id,
                'type' => 'department',
                'title' => $d->name,
                'description' => $d->tag ?? $d->code,
                'url' => route('intranet.departments.show', strtolower($d->code)),
                'tags' => $d->code.' '.($d->description ?? ''),
            ]);
        });

        // Annonces actives
        Announcement::visibleTo($request->user())->where('is_active', true)->get()->each(function (Announcement $a) use ($resultats) {
            $resultats->push([
                'id' => 'announcement-'.$a->id,
                'type' => 'announcement',
                'title' => $a->title,
                'description' => Str::limit((string) $a->body, 80),
                'url' => route('intranet.announcements.show', ['announcement' => $a->id]),
                'tags' => $a->priority,
            ]);
        });

        // Événements à venir et récents
        $events = CalendarEvent::where('event_date', '>=', now()->subMonth());
        $user = $request->user();

        if (! $user->is_super_admin) {
            $departmentIds = $user->departments()->pluck('departments.id');
            $events->where(function ($query) use ($departmentIds): void {
                $query->whereNull('department_id');

                if ($departmentIds->isNotEmpty()) {
                    $query->orWhereIn('department_id', $departmentIds);
                }
            });
        }

        $events->get()->each(function (CalendarEvent $e) use ($resultats) {
            $resultats->push([
                'id' => 'event-'.$e->id,
                'type' => 'event',
                'title' => $e->title,
                'description' => $e->event_date?->format('d/m/Y').' · '.Str::limit((string) $e->description, 60),
                'url' => route('intranet.calendar'),
                'tags' => $e->type,
            ]);
        });

        // Ressources actives
        Resource::where('is_active', true)->get()->each(function (Resource $r) use ($resultats) {
            $resultats->push([
                'id' => 'resource-'.$r->id,
                'type' => 'document',
                'title' => $r->title,
                'description' => Str::limit((string) $r->description, 80),
                'url' => route('intranet.resources.show', $r->id),
                'tags' => $r->file_type.' '.(is_array($r->tags) ? implode(' ', $r->tags) : (string) $r->tags),
            ]);
        });

        // Articles publiés de la base de connaissances
        KnowledgeArticle::published()->get()->each(function (KnowledgeArticle $article) use ($resultats) {
            $resultats->push([
                'id' => 'knowledge-article-'.$article->id,
                'type' => 'page',
                'title' => $article->title,
                'description' => Str::limit($article->summary, 80),
                'url' => route('intranet.knowledge-base.show', $article),
                'tags' => $article->keywords.' '.$article->category,
            ]);
        });

        return response()->json($resultats->values());
    }
}
