<?php

namespace App\Models\Intranet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'intranet_form_fields';

    protected $fillable = [
        'form_id', 'label', 'type', 'options', 'rules',
        'required', 'help_text', 'condition', 'position',
    ];

    protected $casts = [
        'options'   => 'array',
        'rules'     => 'array',
        'condition' => 'array',
        'required'  => 'boolean',
        'position'  => 'integer',
    ];

    /** Types supportés. */
    public const TYPES = [
        'text', 'textarea', 'number', 'date',
        'select', 'multiselect', 'checkbox',
        'file', 'user', 'table',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** Génère les règles Laravel à partir du JSON `rules` + `required`. */
    public function laravelRules(): array
    {
        $r = [];

        if ($this->required) {
            $r[] = 'required';
        } else {
            $r[] = 'nullable';
        }

        $extra = $this->rules ?? [];

        if ($this->type === 'file') {
            $mimes = $extra['mimes'] ?? 'pdf,jpg,jpeg,png,docx,xlsx';
            $max   = $extra['max']   ?? 10240; // Ko
            $r[] = "file|mimes:{$mimes}|max:{$max}";
        } elseif ($this->type === 'number') {
            if (isset($extra['min'])) $r[] = "min:{$extra['min']}";
            if (isset($extra['max'])) $r[] = "max:{$extra['max']}";
        } elseif (in_array($this->type, ['text', 'textarea'])) {
            $r[] = 'string';
            if (isset($extra['max'])) $r[] = "max:{$extra['max']}";
        } elseif ($this->type === 'date') {
            $r[] = 'date';
        } elseif (in_array($this->type, ['select', 'multiselect'])) {
            $r[] = 'string';
        } elseif ($this->type === 'checkbox') {
            $r[] = 'boolean';
        }

        return $r;
    }
}
