<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Normalisation avant validation.
     *
     * Le matricule est volontairement absent des regles : `validated()` ne
     * validerait pas de toute facon une cle non declaree, mais l'expliciter evite
     * qu'un futur ajout de `matricule` dans `$fillable` n'ouvre par megarde la
     * modification de l'identifiant metier.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'email' => is_string($this->email) ? trim(mb_strtolower($this->email)) : $this->email,
        ]);
    }
}
