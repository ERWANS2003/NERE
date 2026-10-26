<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    /** Rouge de marque NERE, utilisé quand aucun accent n'est défini. */
    public const ACCENT_DEFAUT = '#c83530';

    protected $fillable = [
        'code',
        'tag', 
        'name',
        'description',
        'icon',
        'color',
        'position',
        'is_active',
        // Nouveaux champs intranet
        'email',
        'phone',
        'location', 
        'budget',
        'allow_ticket_creation',
        'parent_id',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
            'allow_ticket_creation' => 'boolean',
            'budget' => 'decimal:2',
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
        return $query
            ->orderByRaw("CASE WHEN code = 'IT' THEN 0 ELSE 1 END")
            ->orderBy('position')
            ->orderBy('name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'tech_level']);
    }

    /**
     * Département parent dans la hiérarchie
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Sous-départements
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Manager du département
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Services proposés par le département (vitrine intranet).
     */
    public function services(): HasMany
    {
        return $this->hasMany(DepartmentService::class);
    }

    /**
     * Formulaires du département.
     */
    public function forms(): HasMany
    {
        return $this->hasMany(DepartmentForm::class);
    }

    /**
     * Annonces rattachées au département.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Teinte d'accent, utilisable dans un attribut `style`.
     *
     * La valeur est modifiable par un administrateur et finit injectée dans le
     * HTML : elle n'est donc reprise que si elle ressemble réellement à une
     * couleur. Un champ Alt text fourré de guillemets ne peut pas s'enfuir de
     * l'attribut, il est simplement ignoré au profit du rouge par défaut.
     */
    public function accentColor(): string
    {
        return is_string($this->color) && preg_match('/^#[0-9a-fA-F]{6}$/', $this->color) === 1
            ? $this->color
            : self::ACCENT_DEFAUT;
    }

    /**
     * Fichiers d'illustration livrés dans public/images/departments, indexés
     * par code de département. Source unique de vérité : les vues ne
     * recalculent plus de slug fragile à partir du nom.
     *
     * @var array<string, string>
     */
    private const ILLUSTRATIONS = [
        'ADMIN' => '01-administration.jpg',
        'RH' => '02-ressources-humaines.jpg',
        'SEC' => '03-securite.jpg',
        'MINING' => '04-mining.jpg',
        'HSE' => '05-hse.jpg',
        'PROC' => '06-processing.jpg',
        'SCM' => '07-scm.jpg',
        'IT' => '08-it.jpg',
        'RELCOM' => '09-relations-communautaires.jpg',
    ];

    /**
     * Noms et tags alternatifs utilisés dans certaines bases déjà configurées.
     *
     * @var array<string, string>
     */
    private const ILLUSTRATION_ALIASES = [
        'ADMINISTRATION' => '01-administration.jpg',
        'ADMINISTRATION ET FINANCES' => '01-administration.jpg',
        'ADMINISTRATION FINANCE' => '01-administration.jpg',
        'ADMINISTRATION FINANCES' => '01-administration.jpg',
        'SECURITE' => '03-securite.jpg',
        'SURETE' => '03-securite.jpg',
        'SECURITY' => '03-securite.jpg',
    ];

    /**
     * URL publique de l'illustration du département, ou null si aucune image
     * n'est associée ou présente sur le disque.
     */
    public function illustrationUrl(): ?string
    {
        $fichier = self::ILLUSTRATIONS[$this->code] ?? $this->illustrationFromMetadata();

        if ($fichier === null) {
            return null;
        }

        return file_exists(public_path('images/departments/' . $fichier))
            ? asset('images/departments/' . $fichier)
            : null;
    }

    private function illustrationFromMetadata(): ?string
    {
        foreach ([$this->tag, $this->name] as $identity) {
            if (! is_string($identity) || trim($identity) === '') {
                continue;
            }

            $normalized = Str::upper(Str::ascii($identity));
            $normalized = preg_replace('/[^A-Z0-9]+/', ' ', $normalized) ?? '';
            $normalized = trim(preg_replace('/^DEPARTEMENT\s+/', '', $normalized) ?? '');

            if (isset(self::ILLUSTRATION_ALIASES[$normalized])) {
                return self::ILLUSTRATION_ALIASES[$normalized];
            }
        }

        return null;
    }

    /**
     * Accesseur pratique pour les vues Blade : {{ $department->image_url }}.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->illustrationUrl();
    }
}
