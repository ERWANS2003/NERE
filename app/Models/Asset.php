<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_type_id', 'nom', 'code_inventaire', 'marque', 'modele', 'numero_serie',
        'user_id', 'departement_id', 'site_id', 'statut',
        'date_acquisition', 'date_garantie_fin', 'valeur_acquisition', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_acquisition' => 'date',
            'date_garantie_fin' => 'date',
            'valeur_acquisition' => 'decimal:2',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'asset_ticket');
    }
}
