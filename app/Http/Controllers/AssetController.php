<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Departement;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display list of assets
     */
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->query('per_page'), [10, 20, 25, 50, 100], true)
            ? (int) $request->query('per_page')
            : 20;

        $assets = Asset::query()
            ->with(['type', 'utilisateur', 'departement', 'site'])
            ->when($request->filled('type'), fn ($q) => $q->where('asset_type_id', $request->query('type')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->query('statut')))
            ->when($request->filled('departement'), fn ($q) => $q->where('departement_id', $request->query('departement')))
            ->when($request->filled('site'), fn ($q) => $q->where('site_id', $request->query('site')))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($sub) use ($request) {
                $terme = '%' . $request->query('q') . '%';
                $sub->where('nom', 'like', $terme)
                    ->orWhere('code_inventaire', 'like', $terme)
                    ->orWhere('numero_serie', 'like', $terme)
                    ->orWhere('marque', 'like', $terme)
                    ->orWhere('modele', 'like', $terme);
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('assets.index', compact('assets', 'perPage'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $types = AssetType::orderBy('nom')->get();
        $departements = Departement::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();
        $users = User::where('actif', true)->orderBy('name')->get();

        return view('assets.create', compact('types', 'departements', 'sites', 'users'));
    }

    /**
     * Store asset
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_type_id' => 'required|exists:asset_types,id',
            'nom' => 'required|string|max:255',
            'code_inventaire' => 'required|string|unique:assets,code_inventaire',
            'marque' => 'nullable|string|max:100',
            'modele' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'departement_id' => 'nullable|exists:departements,id',
            'site_id' => 'nullable|exists:sites,id',
            'statut' => 'required|in:En stock,En service,En maintenance,Hors service,Réformé',
            'date_acquisition' => 'nullable|date',
            'date_garantie_fin' => 'nullable|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
        ]);

        $asset = Asset::create($data);

        return redirect()->route('assets.show', $asset)->with('success', "Actif {$asset->code_inventaire} créé avec succès.");
    }

    /**
     * Show asset details
     */
    public function show(Asset $asset)
    {
        $asset->load(['type', 'utilisateur', 'departement', 'site', 'tickets']);

        return view('assets.show', compact('asset'));
    }

    /**
     * Show edit form
     */
    public function edit(Asset $asset)
    {
        $asset->load(['type', 'utilisateur', 'departement', 'site']);
        $types = AssetType::orderBy('nom')->get();
        $departements = Departement::where('actif', true)->orderBy('nom')->get();
        $sites = Site::orderBy('nom')->get();
        $users = User::where('actif', true)->orderBy('name')->get();

        return view('assets.edit', compact('asset', 'types', 'departements', 'sites', 'users'));
    }

    /**
     * Update asset
     */
    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'asset_type_id' => 'required|exists:asset_types,id',
            'nom' => 'required|string|max:255',
            'code_inventaire' => 'required|string|unique:assets,code_inventaire,' . $asset->id,
            'marque' => 'nullable|string|max:100',
            'modele' => 'nullable|string|max:100',
            'numero_serie' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'departement_id' => 'nullable|exists:departements,id',
            'site_id' => 'nullable|exists:sites,id',
            'statut' => 'required|in:En stock,En service,En maintenance,Hors service,Réformé',
            'date_acquisition' => 'nullable|date',
            'date_garantie_fin' => 'nullable|date',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
        ]);

        $asset->update($data);

        return redirect()->route('assets.show', $asset)->with('success', 'Actif mis à jour avec succès.');
    }

    /**
     * Delete asset
     */
    public function destroy(Asset $asset)
    {
        $code = $asset->code_inventaire;
        $asset->delete();

        return redirect()->route('assets.index')->with('success', "Actif {$code} supprimé.");
    }

    /**
     * Link asset to ticket
     */
    public function lierTicket(Request $request, Asset $asset)
    {
        $request->validate(['ticket_id' => 'required|exists:tickets,id']);
        $asset->tickets()->syncWithoutDetaching([$request->ticket_id]);

        return back()->with('success', 'Actif lié au ticket.');
    }
}
