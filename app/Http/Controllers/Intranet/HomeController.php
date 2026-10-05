<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Intranet\Department;
use App\Models\Intranet\Submission;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        // Super admin voit tous les départements actifs
        if ($user->is_super_admin) {
            $departments = Department::active()
                ->with(['services.publishedForms'])
                ->get();

            return view('intranet.home', compact('departments'));
        }

        // Autres : seulement les départements où l'user a un rôle
        $departments = Department::active()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->with(['services.publishedForms'])
            ->get();

        return view('intranet.home', compact('departments'));
    }
}
