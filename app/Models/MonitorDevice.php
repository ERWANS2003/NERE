<?php

namespace App\Models;

use App\Enums\MonitorDeviceType;
use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorDevice extends Model
{
    protected $fillable = ['group_id', 'parent_device_id', 'name', 'host', 'type', 'paused', 'paused_until', 'pause_reason', 'maintenance', 'snmp_v3', ...MonitorGroup::SETTINGS];

    protected $hidden = ['snmp_community', 'snmp_v3'];

    protected function casts(): array
    {
        return [
            'type' => MonitorDeviceType::class,
            'paused' => 'boolean',
            'paused_until' => 'datetime',
            'snmp_community' => 'encrypted',
            'maintenance' => 'array',
            'snmp_v3' => 'encrypted:array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MonitorGroup::class, 'group_id');
    }

    public function parentDevice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_device_id');
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(MonitorSensor::class, 'device_id')->orderBy('id');
    }

    /** Ajoute les capteurs du modèle de ce type d'équipement. */
    public function applyTemplate(bool $snmp): void
    {
        foreach ($this->type->template($snmp) as $sensor) {
            $kind = MonitorSensorKind::from($sensor['kind']);
            $this->sensors()->create($sensor + $kind->defaultLimits());
        }
    }

    public function setting(string $key): mixed
    {
        return $this->{$key} !== null && $this->{$key} !== '' ? $this->{$key} : $this->group->setting($key);
    }

    /** Identifiants SNMP : SNMPv3 s'il est réglé (ici ou sur un groupe parent), sinon la communauté v2c. */
    public function snmpCredentials(): string|array
    {
        $v3 = $this->snmp_v3 ?: MonitorGroup::settingFor($this->group_id, 'snmp_v3');

        return is_array($v3) && filled($v3['user'] ?? null)
            ? $v3
            : (string) ($this->snmp_community ?: MonitorGroup::settingFor($this->group_id, 'snmp_community'));
    }

    public function isPaused(): bool
    {
        return ($this->paused && ($this->paused_until === null || $this->paused_until->isFuture())) || $this->inMaintenance();
    }

    /** @return array{days: list<int>, start: string, end: string}|null fenêtre propre, sinon celle du groupe */
    public function maintenanceWindow(): ?array
    {
        $window = $this->maintenance ?: MonitorGroup::settingFor($this->group_id, 'maintenance');

        return is_array($window) && ! empty($window['days']) && ! empty($window['start']) && ! empty($window['end']) ? $window : null;
    }

    /** Dans la fenêtre de maintenance hebdomadaire (qui peut passer minuit) ? */
    public function inMaintenance(?\Illuminate\Support\Carbon $at = null): bool
    {
        $window = $this->maintenanceWindow();
        if ($window === null) {
            return false;
        }
        $at ??= now();
        $time = $at->format('H:i');
        $days = array_map('intval', $window['days']);
        if ($window['start'] <= $window['end']) {
            return in_array($at->dayOfWeek, $days, true) && $time >= $window['start'] && $time < $window['end'];
        }

        return (in_array($at->dayOfWeek, $days, true) && $time >= $window['start'])
            || (in_array($at->copy()->subDay()->dayOfWeek, $days, true) && $time < $window['end']);
    }

    public function status(): MonitorStatus
    {
        return $this->isPaused()
            ? MonitorStatus::Paused
            : MonitorStatus::worst($this->sensors->map(fn (MonitorSensor $sensor) => $sensor->displayStatus()));
    }
}
