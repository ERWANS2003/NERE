<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rubrique de paie. Les taux, plafonds et barèmes sont des paramètres saisis
 * par le service paie : l'application ne contient aucune valeur réglementaire.
 */
class PayrollRubric extends Model
{
    public const GAIN = 'gain';

    public const DEDUCTION = 'deduction';

    public const CONTRIBUTION = 'contribution';

    public const TAX = 'tax';

    public const KINDS = [
        self::GAIN => 'Gain (prime, indemnité, heures)',
        self::DEDUCTION => 'Retenue (avance, prêt, autre)',
        self::CONTRIBUTION => 'Cotisation sociale',
        self::TAX => 'Impôt sur salaire (barème)',
    ];

    public const BASES = [
        'contributory' => 'Brut cotisable',
        'gross' => 'Brut total',
        'taxable' => 'Brut imposable',
    ];

    protected $fillable = [
        'code',
        'label',
        'kind',
        'is_taxable',
        'is_contributory',
        'base',
        'employee_rate',
        'employer_rate',
        'ceiling',
        'tax_rules',
        'position',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_contributory' => 'boolean',
            'employee_rate' => 'float',
            'employer_rate' => 'float',
            'ceiling' => 'float',
            'tax_rules' => 'array',
            'position' => 'integer',
            'active' => 'boolean',
        ];
    }
}
