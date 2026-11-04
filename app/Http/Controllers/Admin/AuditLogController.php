<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'actor' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AdminAuditLog::query()
            ->with('actor:id,name,email')
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search): void {
                $query->where('action', 'ilike', "%{$search}%")
                    ->orWhere('target_label', 'ilike', "%{$search}%");
            }))
            ->when($validated['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($validated['actor'] ?? null, fn ($query, $actor) => $query->where('actor_id', $actor))
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'categories' => AdminAuditLog::query()->distinct()->orderBy('category')->pluck('category'),
            'actors' => User::query()->whereHas('adminAuditLogs')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
