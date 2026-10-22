<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\IntranetModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleManagementController extends Controller
{
    public function index(IntranetModuleRegistry $modules): View
    {
        return view('admin.modules.index', ['modules' => $modules->all()]);
    }

    public function update(
        Request $request,
        string $module,
        IntranetModuleRegistry $modules,
    ): RedirectResponse {
        abort_unless($modules->has($module), 404);

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $modules->setEnabled($module, $request->boolean('enabled'));

        return redirect()
            ->route('admin.modules.index')
            ->with('success', $validated['enabled']
                ? 'Module activé.'
                : 'Module désactivé. Les données existantes sont conservées.');
    }
}
