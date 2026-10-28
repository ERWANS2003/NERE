<x-guest-layout>
    <x-slot name="titre">Connexion</x-slot>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="input-group">
            <label for="identifiant">Nom d’utilisateur</label>
            <input id="identifiant" 
                   type="text" 
                   name="identifiant" 
                   value="{{ old('identifiant') }}" 
                   required 
                   autofocus 
                   autocomplete="username" 
                   placeholder="Saisissez votre nom d’utilisateur ou adresse e-mail" />
            @if($errors->has('identifiant'))
                <p class="error-message">{{ $errors->first('identifiant') }}</p>
            @endif
        </div>

        <div class="input-group">
            <label for="password">Mot de passe</label>
            <input id="password" 
                   type="password"
                   name="password"
                   required 
                   autocomplete="current-password" 
                   placeholder="••••••••" />
            @if($errors->has('password'))
                <p class="error-message">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <div class="checkbox-group">
            <input id="remember_me" 
                   type="checkbox" 
                   name="remember"
                   value="1">
            <label for="remember_me">Se souvenir de moi</label>
        </div>

        <button type="submit" class="btn-submit">
            Se connecter
        </button>
    </form>
</x-guest-layout>