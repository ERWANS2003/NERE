<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipLine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payslip_id',
        'code',
        'label',
        'kind',
        'base',
        'rate',
        'employer_rate',
        'amount',
        'employer_amount',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'base' => 'float',
            'rate' => 'float',
            'employer_rate' => 'float',
            'amount' => 'float',
            'employer_amount' => 'float',
        ];
    }
}
