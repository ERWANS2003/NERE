<?php

namespace App\Http\Controllers\Intranet;

use App\Enums\TicketCategory;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeBaseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isManager = $this->canManage($user);

        $query = KnowledgeArticle::query()
            ->with('author')
            ->withCount([
                'feedback as helpful_count' => fn ($feedback) => $feedback->where('knowledge_article_feedback.helpful', true),
                'feedback as not_helpful_count' => fn ($feedback) => $feedback->where('knowledge_article_feedback.helpful', false),
            ])
            ->when(! $isManager, fn ($articles) => $articles->published());

        if ($request->filled('search')) {
            $term = trim($request->string('search')->toString());
            $query->where(fn ($articles) => $articles
                ->whereRaw('LOWER(title) LIKE LOWER(?)', ["%{$term}%"])
                ->orWhereRaw('LOWER(summary) LIKE LOWER(?)', ["%{$term}%"])
                ->orWhereRaw('LOWER(content) LIKE LOWER(?)', ["%{$term}%"])
                ->orWhereRaw('LOWER(keywords) LIKE LOWER(?)', ["%{$term}%"]));
        }

        if ($request->filled('category') && TicketCategory::tryFrom($request->string('category')->toString())) {
            $query->where('category', $request->string('category')->toString());
        }

        $articles = $query
            ->orderByDesc('is_published')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('intranet.knowledge-base.index', [
            'articles' => $articles,
            'categories' => TicketCategory::options(),
            'isManager' => $isManager,
        ]);
    }

    public function show(Request $request, KnowledgeArticle $article): View
    {
        abort_unless($article->is_published || $this->canManage($request->user()), 404);

        $article->load('author')->loadCount([
            'feedback as helpful_count' => fn ($feedback) => $feedback->where('knowledge_article_feedback.helpful', true),
            'feedback as not_helpful_count' => fn ($feedback) => $feedback->where('knowledge_article_feedback.helpful', false),
        ]);

        $userVote = $article->feedback()
            ->where('users.id', $request->user()->id)
            ->value('knowledge_article_feedback.helpful');

        return view('intranet.knowledge-base.show', [
            'article' => $article,
            'categories' => TicketCategory::options(),
            'isManager' => $this->canManage($request->user()),
            'userVote' => $userVote === null ? null : (bool) $userVote,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManage($request->user()), 403);

        return view('intranet.knowledge-base.create', [
            'categories' => TicketCategory::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', Rule::enum(TicketCategory::class)],
            'keywords' => ['nullable', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $article = KnowledgeArticle::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['title']),
            'is_published' => $request->boolean('is_published'),
            'author_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('intranet.knowledge-base.show', $article)
            ->with('success', 'L’article a été ajouté à la base de connaissances.');
    }

    public function edit(Request $request, KnowledgeArticle $article): View
    {
        abort_unless($this->canManage($request->user()), 403);

        return view('intranet.knowledge-base.edit', [
            'article' => $article,
            'categories' => TicketCategory::options(),
        ]);
    }

    public function update(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:20000'],
            'category' => ['nullable', Rule::enum(TicketCategory::class)],
            'keywords' => ['nullable', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $article->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['title'], $article),
            'is_published' => $request->boolean('is_published'),
        ]);

        return redirect()
            ->route('intranet.knowledge-base.show', $article)
            ->with('success', 'L’article a été mis à jour.');
    }

    public function destroy(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);

        $article->delete();

        return redirect()
            ->route('intranet.knowledge-base.index')
            ->with('success', 'L’article a été supprimé.');
    }

    public function feedback(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        abort_unless($article->is_published, 404);

        $validated = $request->validate([
            'helpful' => ['required', 'boolean'],
        ]);

        DB::table('knowledge_article_feedback')->updateOrInsert(
            [
                'knowledge_article_id' => $article->id,
                'user_id' => $request->user()->id,
            ],
            [
                'helpful' => $request->boolean('helpful'),
                'updated_at' => now(),
                'created_at' => $article->feedback()
                    ->where('users.id', $request->user()->id)
                    ->exists()
                    ? DB::table('knowledge_article_feedback')
                        ->where('knowledge_article_id', $article->id)
                        ->where('user_id', $request->user()->id)
                        ->value('created_at')
                    : now(),
            ]
        );

        return back()->with(
            'success',
            $request->boolean('helpful')
                ? 'Merci, votre avis confirme que ce guide a aidé.'
                : 'Merci pour votre retour. Vous pouvez contacter le support si le problème persiste.'
        );
    }

    private function canManage(User $user): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        $itDepartment = Department::query()->where('code', 'IT')->first();

        return $itDepartment !== null && $user->canHandleIn($itDepartment->id);
    }

    private function uniqueSlug(string $title, ?KnowledgeArticle $article = null): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = $base;
        $suffix = 2;

        while (KnowledgeArticle::query()
            ->where('slug', $slug)
            ->when($article, fn ($query) => $query->where('id', '<>', $article->getKey()))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
