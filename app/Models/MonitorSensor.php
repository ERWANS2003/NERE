<?php

namespace App\Models;

use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorSensor extends Model
{
    /** Nombre d'échecs de suite avant de passer en panne. */
    public const DOWN_AFTER = 2;

    protected $fillable = ['kind', 'name', 'port', 'path', 'warning_ms', 'paused'];

    protected function casts(): array
    {
        return [
            'kind' => MonitorSensorKind::class,
            'status' => MonitorStatus::class,
            'paused' => 'boolean',
            'checked_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MonitorDevice::class, 'device_id');
    }

    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MonitorReading::class, 'sensor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MonitorEvent::class, 'sensor_id');
    }

    /** L'état affiché : en pause si le capteur ou son équipement l'est. */
    public function displayStatus(): MonitorStatus
    {
        return $this->paused || $this->device?->paused ? MonitorStatus::Paused : $this->status;
    }

    public function target(): string
    {
        return match ($this->kind) {
            MonitorSensorKind::Ping => $this->device->host,
            MonitorSensorKind::Tcp => $this->device->host.':'.$this->port,
            MonitorSensorKind::Http => $this->url(),
        };
    }

    public function url(): string
    {
        $scheme = $this->port === 443 ? 'https' : 'http';
        $port = in_array($this->port, [80, 443], true) ? '' : ':'.$this->port;

        return $scheme.'://'.$this->device->host.$port.($this->path ?: '/');
    }
}
