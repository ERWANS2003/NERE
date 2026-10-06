<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Affiche le tableau de bord intranet
     */
    public function index(Request $request): View
    {
        // Statistiques générales
        $stats = [
            'departments' => Department::count(),
            'forms' => 0, // À implémenter quand les formulaires seront créés
            'submissions' => 0, // À implémenter quand les soumissions seront créées
            'users' => User::where('is_active', true)->count(),
        ];

        // Départements pour l'aperçu (limité à 6)
        $departments = Department::with(['manager', 'users'])
            ->withCount('users')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(6)
            ->get();

        // Activité récente (à implémenter plus tard avec un système de logs)
        $recentActivity = [
            [
                'description' => 'Nouveau département créé: <strong>IT Support</strong>',
                'icon' => 'plus',
                'color' => 'blue',
                'created_at' => now()->subHours(2),
                'time_ago' => 'Il y a 2 heures'
            ],
            [
                'description' => 'Utilisateur <strong>John Doe</strong> ajouté au département Marketing',
                'icon' => 'user-plus',
                'color' => 'green',
                'created_at' => now()->subHours(4),
                'time_ago' => 'Il y a 4 heures'
            ],
            [
                'description' => 'Département <strong>RH</strong> mis à jour',
                'icon' => 'edit',
                'color' => 'yellow',
                'created_at' => now()->subDay(),
                'time_ago' => 'Hier'
            ]
        ];

        return view('intranet.dashboard', compact('stats', 'departments', 'recentActivity'));
    }
}