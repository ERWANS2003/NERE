@extends('layouts.guest')

@section('titre', 'Nouveau mot de passe')

@section('contenu')

    <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-xl bg-brand-soft text-brand">
        <x-icon name="shield-check" class="h-5 w-5" />
    </div>

    <h2 class="font-display text-2xl font-semibold tracking-tight text-strong">
        Nouveau mot de passe
    </h2>
    <p class="mt-1.5 text-sm text-body">
        Choisissez un nouveau mot de passe d'au moins 8 caractères.
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

    <form method="POST" action="{{ route('password.update') }}" class="mt-6" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label for="email" class="mb-1.5 block text-[0.8rem] font-medium text-body">
                Adresse e-mail
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $email) }}"
                required
                autocomplete="username"
                class="w-full rounded-lg border border-line bg-card px-3.5 py-2.5 text-[0.92rem] text-strong outline-none transition focus:border-brand focus:ring-2 focus:ring-brand-ring"
            >
        </div>

        <div class="mb-4">
            <label for="password" class="mb-1.5 block text-[0.8rem] font-medium text-body">
                Nouveau mot de passe
            </label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autofocus
                autocomplete="new-password"
                class="w-full rounded-lg border border-line bg-card px-3.5 py-2.5 text-[0.92rem] text-strong outline-none transition focus:border-brand focus:ring-2 focus:ring-brand-ring"
            >
        </div>

        <div class="mb-5">
            <label for="password_confirmation" class="mb-1.5 block text-[0.8rem] font-medium text-body">
                Confirmer le mot de passe
            </label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                class="w-full rounded-lg border border-line bg-card px-3.5 py-2.5 text-[0.92rem] text-strong outline-none transition focus:border-brand focus:ring-2 focus:ring-brand-ring"
            >
        </div>

        <button type="submit" class="nm-btn nm-btn-primary w-full py-2.5">
            Réinitialiser le mot de passe
        </button>
    </form>

@endsection
