<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'evenement', 'libelle', 'email_actif', 'interne_actif',
        'slack_actif', 'teams_actif', 'sms_actif',
    ];

    protected function casts(): array
    {
        return [
            'email_actif' => 'boolean',
            'interne_actif' => 'boolean',
            'slack_actif' => 'boolean',
            'teams_actif' => 'boolean',
            'sms_actif' => 'boolean',
        ];
    }
}
