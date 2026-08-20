<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KnowledgeArticleController extends Controller
{
    // Phase 9 : Base de connaissances
    public function index(Request $request)
    {
        $articles = KnowledgeArticle::query()
            ->with(['categorie', 'auteur'])
            ->where('publie', true)
            ->when($request->filled('q'), fn ($q) => $q->suggeresPour($request->query('q'), $request->integer('categorie_id') ?: null))
            ->latest()
            ->paginate(15);

        return view('knowledge.index', compact('articles'));
    }

    // Utilisé en AJAX avant la création d'un ticket pour suggérer des articles pertinents
    public function suggerer(Request $request)
    {
        $request->validate(['terme' => 'required|string|min:3']);

        $articles = KnowledgeArticle::suggeresPour($request->string('terme'), $request->integer('categorie_id') ?: null)
            ->get(['id', 'titre', 'ticket_category_id']);

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titre' => 'required|string|max:255',
            'contenu' => 'required|string',
            'ticket_category_id' => 'nullable|exists:ticket_categories,id',
            'mots_cles' => 'nullable|string',
        ]);

        $data['auteur_id'] = Auth::id();
        $article = KnowledgeArticle::create($data);

        return redirect()->route('knowledge.show', $article)->with('success', 'Article publié.');
    }

    public function show(KnowledgeArticle $article)
    {
        $article->increment('vues');

        return view('knowledge.show', compact('article'));
    }
}
