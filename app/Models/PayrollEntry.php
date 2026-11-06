<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollEntry extends Model
{
    protected $fillable = [
        'payroll_period_id',
        'user_id',
        'unpaid_days',
    ];

    protected function casts(): array
    {
        return ['unpaid_days' => 'float'];
    }
}
