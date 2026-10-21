<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class TicketSlaCalculator
{
    public function addBusinessMinutes(CarbonInterface $start, int $minutes): CarbonImmutable
    {
        if ($minutes < 0) {
            throw new InvalidArgumentException('Un délai SLA ne peut pas être négatif.');
        }

        $timezone = (string) config('intranet.sla.timezone');
        $cursor = CarbonImmutable::instance($start)->setTimezone($timezone);
        $remainingSeconds = $minutes * 60;

        while ($remainingSeconds > 0) {
            $interval = config('intranet.sla.schedule.'.$cursor->dayOfWeekIso);

            if (! is_array($interval)) {
                $cursor = $cursor->startOfDay()->addDay();

                continue;
            }

            $opensAt = $cursor->startOfDay()->setTimeFromTimeString($interval['start']);
            $closesAt = $cursor->startOfDay()->setTimeFromTimeString($interval['end']);

            if ($cursor < $opensAt) {
                $cursor = $opensAt;
            }

            if ($cursor >= $closesAt) {
                $cursor = $cursor->startOfDay()->addDay();

                continue;
            }

            $availableSeconds = $closesAt->getTimestamp() - $cursor->getTimestamp();
            if ($remainingSeconds <= $availableSeconds) {
                return $cursor->addSeconds($remainingSeconds);
            }

            $remainingSeconds -= $availableSeconds;
            $cursor = $cursor->startOfDay()->addDay();
        }

        return $cursor;
    }

    public function warningAt(CarbonInterface $start, int $targetMinutes): CarbonImmutable
    {
        $warningPercent = (int) config('intranet.sla.warning_percent');
        $warningMinutes = (int) ceil($targetMinutes * $warningPercent / 100);

        return $this->addBusinessMinutes($start, $warningMinutes);
    }
}
