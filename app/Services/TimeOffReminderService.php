<?php

namespace App\Services;

use App\Models\TimeOffRequest;
use App\Models\User;
use App\Notifications\TimeOffManagerReminder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class TimeOffReminderService
{
    private const REMINDER_AFTER_HOURS = 48;

    /** @return array{sent: int, unroutable: int} */
    public function sendDueReminders(): array
    {
        $cutoff = now()->subHours(self::REMINDER_AFTER_HOURS);
        $requestIds = TimeOffRequest::query()
            ->where('status', 'pending')
            ->whereNull('manager_reminder_sent_at')
            ->where('created_at', '<=', $cutoff)
            ->pluck('id');
        $result = ['sent' => 0, 'unroutable' => 0];

        foreach ($requestIds as $requestId) {
            $outcome = DB::transaction(function () use ($requestId, $cutoff): string {
                $timeOffRequest = TimeOffRequest::query()
                    ->with(['user.manager', 'policy'])
                    ->lockForUpdate()
                    ->find($requestId);

                if (! $timeOffRequest
                    || $timeOffRequest->status !== 'pending'
                    || $timeOffRequest->manager_reminder_sent_at
                    || $timeOffRequest->created_at->gt($cutoff)) {
                    return 'skipped';
                }

                $manager = $timeOffRequest->user?->manager;
                $recipients = $manager && $manager->actif
                    ? collect([$manager])
                    : User::query()->where('is_super_admin', true)->where('actif', true)->get();

                if ($recipients->isEmpty()) {
                    Log::warning('Rappel de congé sans destinataire actif.', [
                        'time_off_request_id' => $timeOffRequest->id,
                        'user_id' => $timeOffRequest->user_id,
                    ]);

                    return 'unroutable';
                }

                Notification::send($recipients, new TimeOffManagerReminder($timeOffRequest));
                $timeOffRequest->update(['manager_reminder_sent_at' => now()]);

                return 'sent';
            });

            if ($outcome === 'sent') {
                $result['sent']++;
            } elseif ($outcome === 'unroutable') {
                $result['unroutable']++;
            }
        }

        return $result;
    }
}
