<?php

namespace App\Jobs;

use App\Models\MonitorDiscovery;
use App\Services\Monitor\NetworkScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class RunNetworkDiscovery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $discoveryId)
    {
        $this->onQueue(config('monitor.queue'));
    }

    public function handle(NetworkScanner $scanner): void
    {
        $discovery = MonitorDiscovery::with('group')->find($this->discoveryId);
        if (! $discovery || $discovery->status !== 'pending') {
            return;
        }
        $discovery->update(['status' => 'running']);
        $v3 = $discovery->group?->setting('snmp_v3');
        $community = is_array($v3) && filled($v3['user'] ?? null) ? $v3 : (string) ($discovery->group?->setting('snmp_community') ?? config('monitor.snmp_community'));

        try {
            $discovery->update(['status' => 'done', 'results' => $scanner->scan($discovery->cidr, $community)]);
        } catch (Throwable $exception) {
            $discovery->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 500)]);
        }
    }
}
