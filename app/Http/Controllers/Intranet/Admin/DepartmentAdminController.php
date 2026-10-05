<?php

namespace App\Http\Controllers\Intranet\Admin;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Service;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentAdminController extends Controller
{
    public function __construct()
    {
        // Super admin uniquement
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->is_super_admin, 403);
            return $next($request);
        });
    }

    public function index()
    {
        $departments = Department::orderBy('position')->withCount(['users', 'services'])->get();
        return view('intranet.admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:30|unique:intranet_departments,code',
            'tag'         => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon'        => 'nullable|string|max:60',
            'color'       => 'nullable|string|max:20',
            'position'    => 'integer|min:0',
        ]);

        Department::create($data);

        return back()->with('success', "Département {$data['name']} créé.");
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'tag'         => 'required|string|max:50',
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon'        => 'nullable|string|max:60',
            'color'       => 'nullable|string|max:20',
            'position'    => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);

        $department->update($data);

        return back()->with('success', "Département mis à jour.");
    }

    public function assignUser(Request $request, Department $department)
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'role'       => 'required|in:user,technician,director',
            'tech_level' => 'nullable|integer|in:1,2,3',
        ]);

        $department->users()->syncWithoutDetaching([
            $data['user_id'] => [
                'role'       => $data['role'],
                'tech_level' => $data['tech_level'] ?? null,
            ],
        ]);

        return back()->with('success', 'Membre affecté au département.');
    }

    public function removeUser(Request $request, Department $department)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $department->users()->detach($request->user_id);
        return back()->with('success', 'Membre retiré du département.');
    }
}
