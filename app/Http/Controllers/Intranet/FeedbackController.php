<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function create(Request $request): View
    {
        return view('intranet.feedback.create', [
            'categories' => Feedback::CATEGORIES,
            'myFeedback' => Feedback::where('user_id', $request->user()->id)->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(Feedback::CATEGORIES))],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'message' => ['required', 'string', 'max:5000'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        $feedback = Feedback::create([
            'user_id' => $request->boolean('anonymous') ? null : $request->user()->id,
            'category' => $validated['category'],
            'rating' => $validated['rating'] ?? null,
            'message' => $validated['message'],
        ]);

        Notification::send(
            User::where('is_super_admin', true)->where('actif', true)->get(),
            new FeedbackReceived($feedback)
        );

        return redirect()
            ->route('intranet.feedback.create')
            ->with('success', 'Merci ! Votre avis a bien été transmis à l’équipe de l’intranet.');
    }
}
