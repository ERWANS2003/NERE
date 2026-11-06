<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'employee',
        'gross',
        'contributory_base',
        'taxable_base',
        'employee_contributions',
        'employer_contributions',
        'tax',
        'deductions',
        'net',
        'stale',
    ];

    protected function casts(): array
    {
        return [
            'employee' => 'array',
            'gross' => 'float',
            'contributory_base' => 'float',
            'taxable_base' => 'float',
            'employee_contributions' => 'float',
            'employer_contributions' => 'float',
            'tax' => 'float',
            'deductions' => 'float',
            'net' => 'float',
            'stale' => 'boolean',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class)->orderBy('position');
    }
}
