<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorGroup extends Model
{
    public const SETTINGS = ['interval_seconds', 'notify_delay_minutes', 'notify_repeat_minutes', 'escalate_after_minutes', 'snmp_community'];

    protected $fillable = ['parent_id', 'name', ...self::SETTINGS];

    protected $hidden = ['snmp_community'];

    protected function casts(): array
    {
        return ['snmp_community' => 'encrypted'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(MonitorDevice::class, 'group_id')->orderBy('name');
    }

    /** Réglage du groupe, sinon d'un groupe parent, sinon la valeur par défaut. */
    public function setting(string $key): mixed
    {
        $group = $this;
        $seen = [];
        while ($group && ! isset($seen[$group->id])) {
            if ($group->{$key} !== null && $group->{$key} !== '') {
                return $group->{$key};
            }
            $seen[$group->id] = true;
            $group = $group->parent_id ? self::find($group->parent_id) : null;
        }

        return config('monitor.'.$key);
    }

    /** Chemin complet, ex. « Mine > Zone du bois ». */
    public function path(): string
    {
        $names = [];
        $group = $this;
        $seen = [];
        while ($group && ! isset($seen[$group->id])) {
            array_unshift($names, $group->name);
            $seen[$group->id] = true;
            $group = $group->parent_id ? self::find($group->parent_id) : null;
        }

        return implode(' > ', $names);
    }

    /** Ce groupe et tous ses sous-groupes. @return list<int> */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        for ($i = 0; $i < count($ids); $i++) {
            array_push($ids, ...self::query()->where('parent_id', $ids[$i])->pluck('id')->diff($ids)->all());
        }

        return $ids;
    }
}
