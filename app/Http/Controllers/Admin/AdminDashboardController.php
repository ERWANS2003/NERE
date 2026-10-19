<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('actif', true)->count(),
            'inactiveUsers' => User::where('actif', false)->count(),
            'administrators' => User::where('is_super_admin', true)->where('actif', true)->count(),
            'departments' => Department::count(),
            'roles' => Role::count(),
        ]);
    }
}
