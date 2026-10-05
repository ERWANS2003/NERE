<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Authentifie la demande.
     *
     * L'identifiant accepte indifféremment le matricule RH ou l'adresse e-mail :
     * sur le terrain, les salaries connaissent leur matricule, pas forcement leur
     * adresse professionnelle. La distinction se fait en base, jamais a l'aveugle.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = $this->userByIdentifiant();

        // Meme message pour « compte inconnu » et « mot de passe faux » : sinon la
        // page de connexion revele quels matricules existent sur le site.
        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'identifiant' => trans('auth.failed'),
            ]);
        }

        // Verrouillage en base verifie AVANT le mot de passe. Deux raisons : un
        // compte bloque doit rendre le meme message quelle que soit la saisie, et
        // on evite de hacher un mot de passe qui sera refuse de toute facon.
        if ($user->isLockedOut()) {
            $this->throwLockedOut($user);
        }

        if (! Auth::validate([
            'password' => $this->string('password')->value(),
            ...$this->credentialsFor($user),
        ])) {
            $user->registerFailedAttempt();
            RateLimiter::hit($this->throttleKey());

            // La tentative qui vient d'echouer est peut-etre celle qui arme le
            // verrouillage : on le dit immediatement plutot que de laisser
            // l'utilisateur retenter pour decouvrir un blocage.
            if ($user->fresh()->isLockedOut()) {
                $this->throwLockedOut($user->fresh());
            }

            throw ValidationException::withMessages([
                'identifiant' => trans('auth.failed'),
            ]);
        }

        // Un compte desactive n'obtient jamais de session, meme avec le bon mot de
        // passe. Le message est distinct du mot de passe faux pour que la personne
        // sache qu'il faut contacter l'administration plutot que ressaisir.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'identifiant' => trans('auth.inactive'),
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        $user->clearFailedAttempts();
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Retrouve le compte par matricule ou par adresse e-mail.
     *
     * L'identifiant accepte indiffemment les deux : sur le terrain, les salaries
     * connaissent leur matricule, pas forcement leur adresse professionnelle. La
     * distinction se fait en base, jamais a l'aveugle.
     *
     * L'e-mail est compare sans casse, comme toute adresse. Le matricule en revanche
     * est compare tel quel : c'est un identifiant fonctionnel saisi au clavier, et
     * le normaliser en minuscules empecherait un matricule mixte (`AB123`) de
     * fonctionner.
     */
    private function userByIdentifiant(): ?User
    {
        return User::query()
            ->where('email', $this->identifiantNormalise())
            ->first()
            ?? User::query()
                ->where('matricule', $this->identifiantBrut())
                ->first();
    }

    /**
     * Contraintes d'authentification reelles pour l'utilisateur retrouve.
     *
     * Auth::validate() a besoin de l'identifiant exact present en base : passer
     * `email` quand l'utilisateur s'est connecte avec son matricule ne
     * correspondrait a aucune ligne.
     *
     * @return array<string, string>
     */
    private function credentialsFor(User $user): array
    {
        return $this->identifiantNormalise() === Str::lower($user->email)
            ? ['email' => $user->email]
            : ['matricule' => (string) $user->matricule];
    }

    private function identifiantBrut(): string
    {
        return Str::trim($this->string('identifiant')->value());
    }

    private function identifiantNormalise(): string
    {
        return Str::lower($this->identifiantBrut());
    }

    /**
     * Double verrouillage, volontairement redondant :
     * - le limiteur de debit protege l'infrastructure (par IP + identifiant) ;
     * - le verrouillage en base protege le compte meme si le cache est purge ou
     *   si l'intrus repartit depuis une autre machine.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), User::MAX_LOGIN_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identifiant' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function throwLockedOut(User $user): never
    {
        // Remaining time computed on timestamps rather than via
        // `locked_until->diffInSeconds(now())`: with Carbon 3 that call is signed,
        // and a lockout scheduled in the future would yield a NEGATIVE duration,
        // producing "Reessayez dans -15 minutes".
        $seconds = max(1, $user->locked_until->getTimestamp() - now()->getTimestamp());

        throw ValidationException::withMessages([
            'identifiant' => trans('auth.locked', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Cle de limitation : identifiant + IP. Viser l'identifiant seul bloquerait
     * un collegue legitime partageant la meme adresse IP sur le reseau de la mine.
     *
     * La cle est normalisee en minuscules, sinon un intrus contournerait le
     * limiteur enchangant la casse de l'identifiant a chaque tentative.
     */
    public function throttleKey(): string
    {
        return Str::transliterate('login|'.$this->identifiantNormalise().'|'.$this->ip());
    }
}
