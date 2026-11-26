<?php

namespace App\Models;

use App\Enums\MonitorSensorKind;
use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitorSensor extends Model
{
    /** Échecs de suite avant la panne : le premier donne un avertissement. */
    public const DOWN_AFTER = 2;

    protected $attributes = ['status' => 'unknown', 'failures' => 0, 'paused' => false, 'escalated' => false];

    protected $fillable = ['kind', 'name', 'port', 'parameter', 'unit', 'interval_seconds', 'warn_above', 'error_above', 'warn_below', 'error_below', 'paused', 'paused_until', 'pause_reason'];

    protected function casts(): array
    {
        return [
            'kind' => MonitorSensorKind::class,
            'status' => MonitorStatus::class,
            'paused' => 'boolean',
            'paused_until' => 'datetime',
            'state' => 'array',
            'escalated' => 'boolean',
            'checked_at' => 'datetime',
            'next_check_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'acknowledged_until' => 'datetime',
            'notified_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'warn_above' => 'float',
            'error_above' => 'float',
            'warn_below' => 'float',
            'error_below' => 'float',
            'last_value' => 'float',
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

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(MonitorReading::class, 'sensor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MonitorEvent::class, 'sensor_id');
    }

    public function isPaused(): bool
    {
        return ($this->paused && ($this->paused_until === null || $this->paused_until->isFuture()))
            || $this->device->isPaused();
    }

    /** L'état affiché : la pause manuelle (capteur ou équipement) prime sur le dernier résultat. */
    public function displayStatus(): MonitorStatus
    {
        return $this->isPaused() ? MonitorStatus::Paused : $this->status;
    }

    public function interval(): int
    {
        return (int) ($this->interval_seconds ?: $this->device->setting('interval_seconds'));
    }

    public function unitLabel(): string
    {
        return $this->unit ?: $this->kind->unit();
    }

    public function formatValue(?float $value = null): string
    {
        $value ??= $this->last_value;
        if ($value === null) {
            return '-';
        }
        $digits = abs($value) >= 100 || floor($value) == $value ? 0 : 1;

        return trim(number_format($value, $digits, ',', ' ').' '.$this->unitLabel());
    }

    public function target(): string
    {
        $host = $this->device->host;

        return match ($this->kind) {
            MonitorSensorKind::Tcp, MonitorSensorKind::Ssl => $host.':'.$this->port,
            MonitorSensorKind::Http => $this->url(),
            default => $host,
        };
    }

    public function url(): string
    {
        $scheme = $this->port === 443 ? 'https' : 'http';
        $port = in_array($this->port, [80, 443, null], true) ? '' : ':'.$this->port;

        return $scheme.'://'.$this->device->host.$port.($this->parameter ?: '/');
    }

    /** @return list<string> */
    public function limitsSummary(): array
    {
        $unit = $this->unitLabel();

        return array_values(array_filter([
            $this->error_above !== null ? "panne au-dessus de {$this->formatValue($this->error_above)}" : null,
            $this->warn_above !== null ? "avertissement au-dessus de {$this->formatValue($this->warn_above)}" : null,
            $this->warn_below !== null ? "avertissement en dessous de {$this->formatValue($this->warn_below)}" : null,
            $this->error_below !== null ? "panne en dessous de {$this->formatValue($this->error_below)}" : null,
        ]));
    }
}
