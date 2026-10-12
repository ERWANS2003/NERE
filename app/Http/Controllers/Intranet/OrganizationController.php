<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\Intranet\IntranetCache;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(private readonly IntranetCache $cache)
    {
    }

    /**
     * Organigramme : Direction générale → branches → départements.
     *
     * Les branches viennent de config('intranet.organization.branches'). Le
     * directeur général est l'utilisateur actif de niveau hiérarchique 0
     * (users.org_level). Le chef d'un département est departments.manager_id.
     *
     * Le calcul des branches (requêtes + construction des cartes + URLs) est
     * coûteux et change rarement : il est mémorisé 1 h et invalidé par
     * l'observateur dès qu'un département ou un utilisateur bouge.
     */
    public function index(): View
    {
        $director = User::where('actif', true)
            ->where('org_level', 0)
            ->orderBy('id')
            ->first();

        $branches = $this->cache->structure(
            'intranet.organization.branches',
            fn () => $this->buildBranches(),
        );

        $stats = $this->cache->structure(
            'intranet.departments.active',
            fn () => [
                'departments' => Department::active()->count(),
                'employees' => User::where('actif', true)->count(),
            ],
        );

        return view('intranet.organization.index', compact('director', 'branches', 'stats'));
    }

    /**
     * Construit les branches de l'organigramme à partir de la configuration et
     * des départements actifs.
     *
     * @return array<int, array{name: string, departments: array<int, array<string, mixed>>}>
     */
    private function buildBranches(): array
    {
        $departments = Department::active()
            ->with(['manager', 'users'])
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->keyBy('code');

        $branches = [];
        $placed = [];

        foreach (config('intranet.organization.branches', []) as $branch) {
            $codes = $branch['departments'] ?? [];
            $cards = [];

            foreach ($codes as $code) {
                if ($departments->has($code)) {
                    $cards[] = $this->departmentCard($departments->get($code));
                    $placed[] = $code;
                }
            }

            if ($cards !== []) {
                $branches[] = ['name' => $branch['name'], 'departments' => $cards];
            }
        }

        // Un département créé plus tard ne doit pas disparaître de l'organigramme.
        // (reject plutôt que except : except() d'une collection Eloquent filtre sur l'id.)
        $others = $departments->reject(fn (Department $d) => in_array($d->code, $placed, true));
        if ($others->isNotEmpty()) {
            $branches[] = [
                'name' => 'Autres',
                'departments' => $others->map(fn (Department $d) => $this->departmentCard($d))->values()->all(),
            ];
        }

        return $branches;
    }

    /**
     * Données d'un département, sérialisables pour le modal de détail.
     *
     * @return array<string, mixed>
     */
    private function departmentCard(Department $department): array
    {
        $members = $department->users
            ->where('actif', true)
            ->sortBy('name')
            ->values();

        $head = $department->manager;

        return [
            'code' => $department->code,
            'name' => $department->name,
            'tag' => $department->tag,
            'color' => $department->accentColor(),
            'description' => $department->description,
            'url' => route('intranet.departments.show', strtolower($department->code)),
            'directory_url' => route('intranet.directory', ['department' => $department->id]),
            'head' => $head ? [
                'name' => $head->name,
                'title' => $head->job_title,
                'initials' => $head->initials,
            ] : null,
            'count' => $members->count(),
            'members' => $members->map(fn (User $u) => [
                'name' => $u->name,
                'title' => $u->job_title,
                'initials' => $u->initials,
                'email' => $u->email,
                'is_head' => $head !== null && $u->id === $head->id,
            ])->all(),
        ];
    }
}
