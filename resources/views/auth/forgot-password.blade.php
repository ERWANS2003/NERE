@extends('layouts.guest')

@section('titre', 'Mot de passe oublié')

@section('contenu')

    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand">
        <x-icon name="lock" class="h-5 w-5" />
    </div>

    <h2 class="font-display text-2xl font-semibold tracking-tight text-strong">
        Mot de passe oublié
    </h2>
    <p class="mt-1.5 text-sm text-body">
        Saisissez votre adresse e-mail professionnelle. Nous vous enverrons un lien
        pour choisir un nouveau mot de passe.
    </p>

    @if ($errors->any())
        <div role="alert" class="mt-5 flex gap-2.5 rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700">
            <x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" />
            <div>
                @foreach ($errors->all() as $erreur)
                    <div>{{ $erreur }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if (session('success'))
        <div role="status" class="mt-5 flex gap-2.5 rounded-lg border border-success-100 bg-success-50 p-3 text-sm text-success-700">
            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 flex-none" />
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6" novalidate>
        @csrf

        <div class="mb-4">
            <label for="email" class="mb-1.5 block text-[0.8rem] font-medium text-body">
                Adresse e-mail
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                placeholder="prenom.nom@nere-mining.bf"
                class="w-full rounded-lg border border-line bg-card px-3.5 py-2.5 text-[0.92rem] text-strong outline-none transition focus:border-brand focus:ring-2 focus:ring-brand-ring"
            >
        </div>

        <button type="submit" class="nm-btn nm-btn-primary w-full py-2.5">
            Envoyer le lien de réinitialisation
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-muted">
        <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand hover:underline">
            <x-icon name="back" class="h-3.5 w-3.5" />
            Retour à la connexion
        </a>
    </p>

@endsection
