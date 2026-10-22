<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use App\Support\IntranetModuleRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketManagementController extends Controller
{
    public function index(Request $request, IntranetModuleRegistry $modules): View
    {
        $query = Ticket::query()->with(['requester', 'assignee', 'department']);

        if ($request->filled('status') && TicketStatus::tryFrom($request->string('status')->toString())) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('department') && $request->integer('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(fn ($tickets) => $tickets
                ->where('number', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"));
        }

        return view('admin.tickets.index', [
            'tickets' => $query->latest()->paginate(25)->withQueryString(),
            'statuses' => TicketStatus::options(),
            'priorities' => TicketPriority::options(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'moduleEnabled' => $modules->enabled('tickets'),
        ]);
    }
}
