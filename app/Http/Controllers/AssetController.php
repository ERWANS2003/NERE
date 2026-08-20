<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    // Phase 8 : Gestion des actifs
    public function index(Request $request)
    {
        $assets = Asset::query()
            ->with(['type', 'utilisateur', 'departement', 'site'])
            ->when($request->filled('type'), fn ($q) => $q->where('asset_type_id', $request->query('type')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->query('statut')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $terme = $request->query('q');
                $sub->where('nom', 'like', "%{$terme}%")
                    ->orWhere('code_inventaire', 'like', "%{$terme}%")
                    ->orWhere('numero_serie', 'like', "%{$terme}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('assets.index', compact('assets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_type_id' => 'required|exists:asset_types,id',
            'nom' => 'required|string|max:255',
            'code_inventaire' => 'required|string|unique:assets,code_inventaire',
            'marque' => 'nullable|string',
            'modele' => 'nullable|string',
            'numero_serie' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'departement_id' => 'nullable|exists:departements,id',
            'site_id' => 'nullable|exists:sites,id',
            'statut' => 'required|in:En stock,En service,En maintenance,Hors service,Réformé',
            'date_acquisition' => 'nullable|date',
            'date_garantie_fin' => 'nullable|date',
        ]);

        $asset = Asset::create($data);

        return redirect()->route('assets.index')->with('success', "Actif {$asset->code_inventaire} créé.");
    }

    // Lier un actif à un ticket
    public function lierTicket(Request $request, Asset $asset)
    {
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);
        $asset->tickets()->syncWithoutDetaching([$request->ticket_id]);

        return back()->with('success', 'Actif lié au ticket.');
    }
}
