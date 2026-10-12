<x-guest-layout>
    <x-slot name="titre">Connexion</x-slot>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Identifiant (email or matricule) -->
        <div class="input-group">
            <label for="identifiant">EMAIL</label>
            <input id="identifiant" 
                   type="text" 
                   name="identifiant" 
                   value="{{ old('identifiant') }}" 
                   required 
                   autofocus 
                   autocomplete="username" 
                   placeholder="your@email.com" />
            @if($errors->has('identifiant'))
                <div class="error-message">{{ $errors->first('identifiant') }}</div>
            @endif
        </div>

        <!-- Password -->
        <div class="input-group">
            <label for="password">PASSWORD</label>
            <input id="password" 
                   type="password"
                   name="password"
                   required 
                   autocomplete="current-password" 
                   placeholder="••••••••" />
            @if($errors->has('password'))
                <div class="error-message">{{ $errors->first('password') }}</div>
            @endif
        </div>

        <!-- Remember Me -->
        <div class="checkbox-group">
            <input id="remember_me" 
                   type="checkbox" 
                   name="remember">
            <label for="remember_me">Remember me</label>
        </div>

        <button type="submit" class="btn-submit">
            SIGN IN
        </button>
    </form>
</x-guest-layout>