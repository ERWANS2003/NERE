<?php

namespace App\Services\Monitor;

use App\Enums\DepartmentRole;
use App\Enums\MonitorStatus;
use App\Models\Department;
use App\Models\MonitorSensor;
use App\Models\User;
use App\Notifications\MonitorAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Déclencheur d'état comme dans PRTG : prévenir quand un capteur est en panne
 * depuis le délai réglé, répéter tant qu'il l'est, escalader s'il n'est pas pris
 * en compte, et prévenir de la fin de la panne.
 */
class MonitorNotifier
{
    public function statusChanged(MonitorSensor $sensor, MonitorStatus $previous): void
    {
        $wasDown = in_array($previous, [MonitorStatus::Down, MonitorStatus::DownAcknowledged], true);
        $isDown = in_array($sensor->status, [MonitorStatus::Down, MonitorStatus::DownAcknowledged], true);

        if ($wasDown && ! $isDown) {
            // Fin d'alerte, seulement si la panne avait été annoncée.
            if ($sensor->notified_at) {
                Notification::send($this->team(), new MonitorAlert($sensor, 'recovered'));
            }
            $sensor->forceFill(['notified_at' => null, 'last_notified_at' => null, 'escalated' => false])->save();
        }
    }

    public function run(): void
    {
        MonitorSensor::query()
            ->with('device.group')
            ->where('status', MonitorStatus::Down)
            ->get()
            ->each(function (MonitorSensor $sensor) {
                if ($sensor->isPaused()) {
                    return;
                }
                $since = $sensor->status_changed_at ?? now();
                $delay = (int) $sensor->device->setting('notify_delay_minutes');
                $repeat = (int) $sensor->device->setting('notify_repeat_minutes');
                $escalate = (int) $sensor->device->setting('escalate_after_minutes');

                if ($sensor->notified_at === null) {
                    if ($since->lte(now()->subMinutes($delay))) {
                        Notification::send($this->team(), new MonitorAlert($sensor, 'down'));
                        $sensor->forceFill(['notified_at' => now(), 'last_notified_at' => now()])->save();
                    }

                    return;
                }

                if ($repeat > 0 && $sensor->last_notified_at?->lte(now()->subMinutes($repeat))) {
                    Notification::send($this->team(), new MonitorAlert($sensor, 'still_down'));
                    $sensor->forceFill(['last_notified_at' => now()])->save();
                }

                if ($escalate > 0 && ! $sensor->escalated && $since->lte(now()->subMinutes($escalate))) {
                    Notification::send($this->managers(), new MonitorAlert($sensor, 'escalated'));
                    $sensor->forceFill(['escalated' => true])->save();
                }
            });
    }

    /** Techniciens et responsables IT, et administrateurs. @return Collection<int, User> */
    public function team(): Collection
    {
        $it = $this->itDepartmentId();
        $handlers = $it ? User::query()->where('actif', true)->handlersOf($it)->get() : collect();

        return $handlers->merge($this->admins())->unique('id')->values();
    }

    /** Escalade : responsables du département IT et administrateurs. @return Collection<int, User> */
    public function managers(): Collection
    {
        $it = $this->itDepartmentId();
        $directors = $it
            ? User::query()->where('actif', true)->whereHas('departments', fn ($q) => $q->where('departments.id', $it)->where('department_user.role', DepartmentRole::Director->value))->get()
            : collect();

        return $directors->merge($this->admins())->unique('id')->values();
    }

    private function admins(): Collection
    {
        return User::query()->where('actif', true)->where('is_super_admin', true)->get();
    }

    private function itDepartmentId(): ?int
    {
        return Department::query()->where('code', 'IT')->where('is_active', true)->value('id');
    }
}
