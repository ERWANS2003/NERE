<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\DepartmentRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\IntranetModuleRegistry;
use Illuminate\View\View;

/**
 * Page vitrine d'un département (accessible par son code, ex. /intranet/departments/it).
 *
 * Distincte du CRUD d'administration (DepartmentController, sous /manage) :
 * ici on prépare l'affichage public à onglets (Services / Formulaires / Équipe).
 */
class DepartmentShowController extends Controller
{
    public function __invoke(string $code, IntranetModuleRegistry $modules): View
    {
        $department = Department::query()
            ->where('code', strtoupper($code))
            ->firstOrFail();

        $formsEnabled = $modules->enabled('forms');

        $services = $formsEnabled
            ? $department->services()
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->get()
            : collect();

        $forms = $formsEnabled
            ? $department->forms()
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->get()
            : collect();

        $team = $this->buildTeam($department);

        return view('intranet.departments.show', [
            'department' => $department,
            'services' => $services,
            'forms' => $forms,
            'formsEnabled' => $formsEnabled,
            'team' => $team,
            'memberCount' => $team->count(),
        ]);
    }

    /**
     * Équipe du département, triée par niveau hiérarchique (directeur, puis
     * technicien, puis utilisateur) et par nom. Le rôle du pivot department_user
     * est élevé par les rôles système (Chef de département, Technicien).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildTeam(Department $department): \Illuminate\Support\Collection
    {
        $poids = [
            DepartmentRole::Director->value => 0,
            DepartmentRole::Technician->value => 1,
            DepartmentRole::User->value => 2,
        ];

        return $department->users()
            ->where('actif', true)
            ->with('roles.permissions')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                $role = $user->elevatedDepartmentRole(DepartmentRole::tryFrom($user->pivot->role) ?? DepartmentRole::User);

                return [
                    'user' => $user,
                    'role' => $role,
                    'roleLabel' => $role?->label() ?? 'Membre',
                    'techLevel' => $user->elevatedTechLevel($role, $user->pivot->tech_level),
                    'isHead' => $role === DepartmentRole::Director,
                ];
            })
            ->sortBy([
                fn ($a, $b) => ($poids[$a['role']?->value] ?? 9) <=> ($poids[$b['role']?->value] ?? 9),
                fn ($a, $b) => strcmp($a['user']->name, $b['user']->name),
            ])
            ->values();
    }
}
