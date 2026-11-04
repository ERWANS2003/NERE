@extends('admin.layout')

@section('title', 'Avis des utilisateurs')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <p class="text-sm text-gray-600">
            {{ $newCount }} nouvel(s) avis
            @if($averageRating) · note moyenne {{ number_format($averageRating, 1, ',', ' ') }} / 5 @endif
        </p>
        <form method="GET" class="flex flex-wrap gap-2 text-sm">
            <select name="status" class="rounded border-gray-300" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                @foreach(\App\Models\Feedback::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="rounded border-gray-300" onchange="this.form.submit()">
                <option value="">Tous les types</option>
                @foreach(\App\Models\Feedback::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($feedback as $item)
            <article class="rounded-lg border border-gray-200 bg-white p-5">
                <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                    <span class="font-semibold text-gray-900">{{ \App\Models\Feedback::CATEGORIES[$item->category] ?? $item->category }}</span>
                    @if($item->rating)<span class="text-amber-500" title="{{ $item->rating }} sur 5">{{ str_repeat('★', $item->rating) }}</span>@endif
                    <span class="text-gray-500">{{ $item->user?->name ?? 'Anonyme' }} · {{ $item->created_at->format('d/m/Y H:i') }}</span>
                    <span class="rounded px-2 py-0.5 text-xs {{ $item->status === 'new' ? 'bg-red-100 text-red-800' : ($item->status === 'done' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700') }}">
                        {{ \App\Models\Feedback::STATUSES[$item->status] ?? $item->status }}
                    </span>
                </div>
                <p class="whitespace-pre-line text-sm text-gray-800">{{ $item->message }}</p>

                <form method="POST" action="{{ route('admin.feedback.update', $item) }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-start">
                    @csrf
                    @method('PATCH')
                    <select name="status" class="rounded border-gray-300 text-sm">
                        @foreach(\App\Models\Feedback::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($item->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="admin_note" value="{{ $item->admin_note }}" maxlength="2000" placeholder="Réponse visible par l’auteur (facultatif)" class="flex-1 rounded border-gray-300 text-sm">
                    <button class="rounded bg-nere-700 px-4 py-2 text-sm font-semibold text-white hover:bg-nere-800">Enregistrer</button>
                </form>
            </article>
        @empty
            <p class="rounded-lg border border-gray-200 bg-white px-4 py-10 text-center text-gray-500">Aucun avis pour le moment.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $feedback->links() }}</div>
@endsection
