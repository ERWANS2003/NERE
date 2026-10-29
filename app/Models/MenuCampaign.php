<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCampaign extends Model
{
    protected $fillable = [
        'title',
        'link_token',
        'starts_on',
        'ends_on',
        'registration_closes_at',
        'status',
        'created_by',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_closes_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /** Semaines publiées dont les inscriptions ne sont pas encore closes. */
    public function scopeOpenForRegistration(Builder $query): void
    {
        $query->where('status', 'open')
            ->where(function (Builder $query): void {
                $query->whereNull('registration_closes_at')
                    ->orWhere('registration_closes_at', '>', now());
            });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(MenuDay::class, 'campaign_id')->orderBy('date');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(MenuRegistration::class, 'campaign_id');
    }
}
