<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackManagementController extends Controller
{
    public function index(Request $request): View
    {
        $feedback = Feedback::with('user')
            ->when(array_key_exists((string) $request->status, Feedback::STATUSES), fn ($query) => $query->where('status', $request->status))
            ->when(array_key_exists((string) $request->category, Feedback::CATEGORIES), fn ($query) => $query->where('category', $request->category))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.feedback.index', [
            'feedback' => $feedback,
            'newCount' => Feedback::where('status', 'new')->count(),
            'averageRating' => Feedback::whereNotNull('rating')->avg('rating'),
        ]);
    }

    public function update(Request $request, Feedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Feedback::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $feedback->update($validated);

        return back()->with('success', 'Avis mis à jour.');
    }
}
