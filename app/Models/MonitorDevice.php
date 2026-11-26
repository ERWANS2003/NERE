<?php

namespace App\Models;

use App\Enums\MonitorDeviceType;
use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorDevice extends Model
{
    protected $fillable = ['name', 'host', 'type', 'site', 'paused'];

    protected function casts(): array
    {
        return [
            'type' => MonitorDeviceType::class,
            'paused' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (MonitorDevice $device): void {
            foreach ($device->type->defaultSensors() as $sensor) {
                $device->sensors()->create($sensor);
            }
        });
    }

    public function sensors(): HasMany
    {
        return $this->hasMany(MonitorSensor::class, 'device_id')->orderBy('id');
    }

    public function status(): MonitorStatus
    {
        return $this->paused
            ? MonitorStatus::Paused
            : MonitorStatus::worst($this->sensors->map(fn (MonitorSensor $sensor) => $sensor->displayStatus()));
    }
}
