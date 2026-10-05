<?php

namespace App\Models\Intranet;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'intranet_departments';

    protected $fillable = [
        'code', 'tag', 'name', 'description',
        'icon', 'color', 'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position'  => 'integer',
    ];

    /* ── Relations ─────────────────────────────────────────── */

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'intranet_department_user')
            ->withPivot(['role', 'tech_level'])
            ->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('position');
    }

    /* ── Helpers ────────────────────────────────────────────── */

    /** Techniciens triés par niveau. */
    public function technicians()
    {
        return $this->users()->wherePivot('role', 'technician')->orderByPivot('tech_level');
    }

    /** Directeurs du département. */
    public function directors()
    {
        return $this->users()->wherePivot('role', 'director');
    }

    /** Nb de demandes en attente toutes étapes confondues. */
    public function pendingSubmissionsCount(): int
    {
        return $this->submissions()
            ->whereNotIn('status', ['cloturee', 'rejetee', 'annulee'])
            ->count();
    }

    public function submissions()
    {
        return Submission::whereHas('form.service', fn ($q) => $q->where('department_id', $this->id));
    }

    /* ── Scopes ─────────────────────────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position');
    }
}
