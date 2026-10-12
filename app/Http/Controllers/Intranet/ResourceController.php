<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResourceController extends Controller
{
    /**
     * Disque de stockage des fichiers uploadés. Privé : un fichier n'est
     * accessible que via la route download() authentifiée, jamais par URL
     * publique directe. Cohérent avec les pièces jointes de l'intranet.
     */
    private const DISK = 'private';

    /** Taille maximale d'un upload, en kilo-octets (50 Mo). */
    private const MAX_SIZE_KB = 51200;

    /**
     * Extensions acceptées → type stocké dans la colonne `file_type`
     * (enum de la migration : pdf, doc, docx, xls, xlsx, ppt, pptx, video).
     */
    private const EXTENSIONS = [
        'pdf' => 'pdf',
        'doc' => 'doc',
        'docx' => 'docx',
        'xls' => 'xls',
        'xlsx' => 'xlsx',
        'ppt' => 'ppt',
        'pptx' => 'pptx',
        'mp4' => 'video',
        'mov' => 'video',
        'avi' => 'video',
        'webm' => 'video',
    ];

    /**
     * Bibliothèque : recherche plein champ (titre/description/tags), filtres
     * (catégorie, type de fichier, département), compteurs par catégorie,
     * listes « populaires » et « récentes », et marquage des favoris de
     * l'utilisateur courant.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Identifiants des ressources en favori (pour cocher les étoiles).
        $favorites = $user->favoriteResources()->pluck('resources.id')->all();

        // Requête filtrable sur les ressources actives.
        $query = Resource::query()
            ->active()
            ->with(['category', 'department'])
            ->withCount('favoritedBy');

        if ($request->filled('search')) {
            $terme = trim($request->string('search'));
            $query->where(function ($q) use ($terme) {
                $q->where('title', 'ILIKE', "%{$terme}%")
                  ->orWhere('description', 'ILIKE', "%{$terme}%")
                  ->orWhereJsonContains('tags', $terme);
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->integer('category'));
        }

        if ($request->filled('type')) {
            $query->where('file_type', $request->string('type'));
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        // Favoris seuls (onglet « Mes favoris »).
        if ($request->boolean('favorites')) {
            $query->whereIn('id', $favorites ?: [0]);
        }

        $resources = $query->orderBy('title')->get();

        // Catégories avec compteur de ressources actives (sidebar).
        $categories = ResourceCategory::query()
            ->withCount(['resources' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('order')
            ->get();

        // Types de fichiers présents (pour le filtre).
        $types = Resource::active()->distinct()->orderBy('file_type')->pluck('file_type');

        $departments = Department::active()->orderBy('name')->get();

        // Encarts : populaires (plus téléchargés) et récents.
        $popular = Resource::active()->with('category')->popular(5)->get();
        $recent = Resource::active()->with('category')->latest()->limit(5)->get();

        return view('intranet.resources.index', [
            'resources' => $resources,
            'categories' => $categories,
            'types' => $types,
            'departments' => $departments,
            'popular' => $popular,
            'recent' => $recent,
            'favorites' => $favorites,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->integer('category') ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'department' => $request->integer('department') ?: null,
                'favorites' => $request->boolean('favorites'),
            ],
        ]);
    }

    /**
     * Ajoute ou retire la ressource des favoris de l'utilisateur courant.
     * `toggle` renvoie les ids attachés/détachés : on peut donc confirmer l'état.
     */
    public function toggleFavorite(Request $request, Resource $resource): RedirectResponse
    {
        abort_unless($resource->is_active, 404);

        $resultat = $request->user()->favoriteResources()->toggle($resource->id);
        $ajoute = in_array($resource->id, $resultat['attached'], true);

        return back()->with(
            'success',
            $ajoute ? 'Ressource ajoutée à vos favoris.' : 'Ressource retirée de vos favoris.',
        );
    }

    /**
     * Fiche d'une ressource. Consulter la fiche n'est pas un téléchargement :
     * le compteur n'est incrémenté que par download().
     */
    public function show(Resource $resource): View
    {
        abort_unless($resource->is_active, 404);

        $resource->load(['category', 'department', 'uploader']);

        return view('intranet.resources.show', [
            'resource' => $resource,
            'available' => $this->fileExists($resource),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Resource::class);

        return view('intranet.resources.create', [
            'categories' => ResourceCategory::orderBy('order')->get(),
            'departments' => $this->manageableDepartments($request->user()),
            'canPublishForAll' => (bool) $request->user()->is_super_admin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Resource::class);

        $validated = $this->validatedData($request);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $category = ResourceCategory::findOrFail($validated['category_id']);

        // Nom de fichier non devinable, sous-dossier par catégorie.
        $path = $file->storeAs(
            'resources/'.$category->slug,
            uniqid('res_', true).'.'.$extension,
            self::DISK,
        );

        Resource::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'department_id' => $validated['department_id'],
            'file_type' => self::EXTENSIONS[$extension],
            'file_path' => $path,
            'file_size' => $this->humanSize($file->getSize()),
            'tags' => $validated['tags'],
            'uploaded_by' => $request->user()->id,
            'download_count' => 0,
            'is_active' => true,
        ]);

        return redirect()
            ->route('intranet.resources')
            ->with('success', 'Ressource ajoutée à la bibliothèque.');
    }

    public function download(Resource $resource): StreamedResponse
    {
        abort_unless($resource->is_active && $this->fileExists($resource), 404);

        $resource->increment('download_count');

        return Storage::disk(self::DISK)->download(
            $this->storagePath($resource),
            basename((string) $resource->file_path),
        );
    }

    public function destroy(Resource $resource): RedirectResponse
    {
        $this->authorize('delete', $resource);

        // Suppression du fichier sur disque si présent, puis de l'enregistrement.
        if ($this->fileExists($resource)) {
            Storage::disk(self::DISK)->delete($this->storagePath($resource));
        }

        $resource->delete();

        return redirect()
            ->route('intranet.resources')
            ->with('success', 'Ressource supprimée.');
    }

    /**
     * @return array{title:string,description:?string,category_id:int,department_id:?int,tags:array<int,string>}
     */
    private function validatedData(Request $request): array
    {
        $user = $request->user();

        if ($request->input('department_id') === '') {
            $request->merge(['department_id' => null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required', 'exists:resource_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'tags' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_SIZE_KB,
                'mimes:'.implode(',', array_keys(self::EXTENSIONS)),
            ],
        ], [
            'file.mimes' => 'Format non autorisé. Fichiers acceptés : PDF, Word, Excel, PowerPoint, vidéo.',
            'file.max' => 'Le fichier ne doit pas dépasser 50 Mo.',
        ]);

        // Un directeur ne peut rattacher la ressource qu'à un département
        // qu'il dirige ; « toute l'entreprise » (null) reste réservé à l'admin.
        if (! $user->is_super_admin) {
            $autorises = $this->manageableDepartments($user)->pluck('id');

            abort_unless(
                $validated['department_id'] !== null && $autorises->contains((int) $validated['department_id']),
                403,
                "Vous ne pouvez publier que pour un département que vous dirigez.",
            );
        }

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => (int) $validated['category_id'],
            'department_id' => $validated['department_id'] !== null ? (int) $validated['department_id'] : null,
            'tags' => $this->parseTags($validated['tags'] ?? null),
        ];
    }

    /**
     * Transforme « hse, sécurité , politique » en ['hse','sécurité','politique'].
     *
     * @return array<int, string>
     */
    private function parseTags(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    private function manageableDepartments($user): \Illuminate\Support\Collection
    {
        return $user->is_super_admin
            ? Department::active()->orderBy('name')->get()
            : $user->directedDepartments()->sortBy('name')->values();
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' o';
    }

    /** Chemin relatif au disque, sans « / » initial (le seeder en met un). */
    private function storagePath(Resource $resource): string
    {
        return ltrim((string) $resource->file_path, '/');
    }

    private function fileExists(Resource $resource): bool
    {
        return filled($resource->file_path)
            && Storage::disk(self::DISK)->exists($this->storagePath($resource));
    }
}
