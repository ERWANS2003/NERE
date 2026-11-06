<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Élément variable du mois : prime ponctuelle, heures, retenue exceptionnelle. */
class PayrollVariable extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'payroll_rubric_id',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(PayrollRubric::class, 'payroll_rubric_id');
    }
}
