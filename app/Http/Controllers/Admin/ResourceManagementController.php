<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Support\IntranetModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResourceManagementController extends Controller
{
    public function index(Request $request, IntranetModuleRegistry $modules): View
    {
        $resources = Resource::query()
            ->with(['category', 'department', 'uploader'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(fn ($resources) => $resources
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->when(in_array($request->string('status')->toString(), ['active', 'inactive'], true), fn ($query) => $query->where(
                'is_active',
                $request->string('status')->toString() === 'active'
            ))
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.resources.index', [
            'resources' => $resources,
            'categories' => ResourceCategory::withCount('resources')->orderBy('order')->get(),
            'departments' => Department::orderBy('name')->get(),
            'moduleEnabled' => $modules->enabled('resources'),
        ]);
    }

    public function update(Request $request, Resource $resource): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:resource_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['tags'] = collect(explode(',', $validated['tags'] ?? ''))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $resource->update($validated);

        return back()->with('success', 'Ressource mise à jour.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:resource_categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        ResourceCategory::create($validated);

        return back()->with('success', 'Catégorie créée.');
    }

    public function updateCategory(Request $request, ResourceCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('resource_categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Catégorie mise à jour.');
    }

    public function destroyCategory(ResourceCategory $category): RedirectResponse
    {
        if ($category->resources()->exists()) {
            return back()->withErrors([
                'category' => 'Cette catégorie contient encore des ressources. Réaffectez-les avant de la supprimer.',
            ]);
        }

        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
