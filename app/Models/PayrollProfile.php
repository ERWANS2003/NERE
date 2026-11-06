<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PayrollProfile extends Model
{
    public const PAYMENT_METHODS = [
        'virement' => 'Virement bancaire',
        'especes' => 'Espèces',
        'mobile' => 'Paiement mobile',
    ];

    protected $fillable = [
        'user_id',
        'base_salary',
        'category',
        'contract_type',
        'social_security_number',
        'dependents',
        'payment_method',
        'bank_name',
        'bank_account',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'float',
            'dependents' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Primes, indemnités et retenues fixes, reprises chaque mois. */
    public function rubrics(): BelongsToMany
    {
        return $this->belongsToMany(PayrollRubric::class, 'payroll_profile_rubrics')
            ->withPivot('amount')
            ->withTimestamps();
    }
}
