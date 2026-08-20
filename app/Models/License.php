<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    protected $fillable = [
        'nom_logiciel', 'cle_licence', 'fournisseur', 'nombre_postes',
        'date_achat', 'date_expiration', 'asset_id',
    ];

    protected function casts(): array
    {
        return [
            'date_achat' => 'date',
            'date_expiration' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
