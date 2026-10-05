<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'tag',
        'name',
        'description',
        'icon',
        'color',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Restreint la requete aux departements que cet utilisateur est autorise a
     * voir. Source unique de verite : policies, page d'accueil et controleurs
     * appellent tous ce scope, donc une autorisation ne peut pas diverger entre
     * une vue et une route.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->is_super_admin) {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($user) {
            $sub->selectRaw('1')
                ->from('department_user')
                ->whereColumn('department_user.department_id', 'departments.id')
                ->where('department_user.user_id', $user->getKey());
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'tech_level']);
    }

    /** Teinte d'accent : ambre par defaut si le departement n'en definit pas. */
    public function accentColor(): string
    {
        return $this->color ?: '#b45309';
    }
}
