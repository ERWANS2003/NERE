<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectoryController extends Controller
{
    /**
     * Annuaire des employés : recherche par nom/email/poste, filtre par
     * département, tri alphabétique, pagination.
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('actif', true)
            ->with('departments');

        // Recherche texte (nom, email, poste).
        if ($request->filled('search')) {
            $terme = trim($request->string('search'));
            $query->where(function ($q) use ($terme) {
                $q->where('name', 'ILIKE', "%{$terme}%")
                  ->orWhere('email', 'ILIKE', "%{$terme}%")
                  ->orWhere('poste', 'ILIKE', "%{$terme}%")
                  ->orWhere('position_title', 'ILIKE', "%{$terme}%");
            });
        }

        // Filtre par département.
        if ($request->filled('department')) {
            $departmentId = $request->integer('department');
            $query->whereHas('departments', function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            });
        }

        $employees = $query->orderBy('name')->paginate(20)->withQueryString();

        $departments = Department::active()->orderBy('name')->get();

        return view('intranet.directory.index', compact('employees', 'departments'));
    }
}
