@extends('layouts.guest')

@section('titre', 'Connexion')

@section('contenu')

    <h2 style="font-family:'Space Grotesk',sans-serif; font-weight:600; font-size:1.5rem; margin:0 0 0.35rem; letter-spacing:-0.01em;">
        Connexion
    </h2>
    <p style="color:#7d7869; font-size:0.9rem; margin:0 0 1.75rem;">
        Accédez à votre espace de tickets, actifs et rapports.
    </p>

    @if ($errors->any())
        <div style="background:#fbeaea; border:1px solid #eebcbc; color:#a13030; padding:0.75rem 0.9rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.25rem;">
            @foreach ($errors->all() as $erreur)
                <div>{{ $erreur }}</div>
            @endforeach
        </div>
    @endif

    @if (session('success'))
        <div style="background:#e8f4ee; border:1px solid #b9dfc8; color:#256b45; padding:0.75rem 0.9rem; border-radius:8px; font-size:0.85rem; margin-bottom:1.25rem;">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div style="margin-bottom:1.15rem;">
            <label for="email" style="display:block; font-size:0.8rem; font-weight:500; color:#4a463c; margin-bottom:0.4rem;">
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
                style="width:100%; padding:0.7rem 0.85rem; border:1px solid #ddd7c8; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.92rem; background:#fff; color:#1b1f23; outline:none; transition:border-color .15s;"
                onfocus="this.style.borderColor='#e0a52f'"
                onblur="this.style.borderColor='#ddd7c8'"
            >
        </div>

        <div style="margin-bottom:0.6rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:0.4rem;">
                <label for="password" style="font-size:0.8rem; font-weight:500; color:#4a463c;">
                    Mot de passe
                </label>
                <a href="{{ route('password.request') }}" style="font-size:0.78rem; color:#a1782f; text-decoration:none;">
                    Mot de passe oublié ?
                </a>
            </div>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                style="width:100%; padding:0.7rem 0.85rem; border:1px solid #ddd7c8; border-radius:8px; font-family:'Inter',sans-serif; font-size:0.92rem; background:#fff; color:#1b1f23; outline:none; transition:border-color .15s;"
                onfocus="this.style.borderColor='#e0a52f'"
                onblur="this.style.borderColor='#ddd7c8'"
            >
        </div>

        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.82rem; color:#6b6558; margin:1rem 0 1.5rem; cursor:pointer;">
            <input type="checkbox" name="remember" style="accent-color:#e0a52f; width:15px; height:15px;">
            Rester connecté
        </label>

        <button
            type="submit"
            style="width:100%; padding:0.78rem; border:none; border-radius:8px; background:#1b1f23; color:#f7f5f1; font-family:'Inter',sans-serif; font-weight:600; font-size:0.92rem; cursor:pointer; transition:background .15s;"
            onmouseover="this.style.background='#e0a52f'; this.style.color='#1b1f23'"
            onmouseout="this.style.background='#1b1f23'; this.style.color='#f7f5f1'"
        >
            Se connecter
        </button>
    </form>

@endsection
