<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;
use Throwable;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{title: string, body: string, url: string, tag: string}  $payload
     */
    public function __construct(
        public int $subscriptionId,
        public array $payload
    ) {}

    public int $tries = 3;

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(): void
    {
        if (! config('webpush.enabled')) {
            return;
        }

        $subscription = PushSubscription::find($this->subscriptionId);

        if (! $subscription) {
            return;
        }

        $httpClient = new Client([
            'allow_redirects' => false,
            'connect_timeout' => 5,
            'timeout' => 15,
        ]);
        $httpFactory = new HttpFactory;
        $webPush = new WebPush(
            [
                'VAPID' => [
                    'subject' => config('webpush.subject'),
                    'publicKey' => config('webpush.public_key'),
                    'privateKey' => config('webpush.private_key'),
                ],
            ],
            [],
            $httpClient,
            $httpFactory,
            $httpFactory
        );

        $report = $webPush->sendOneNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->public_key,
                    'auth' => $subscription->auth_token,
                ],
                'contentEncoding' => 'aes128gcm',
            ]),
            json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['TTL' => 3600, 'urgency' => 'normal']
        );

        if ($report->isSubscriptionExpired()) {
            $subscription->delete();

            return;
        }

        if (! $report->isSuccess()) {
            $status = $report->getResponse()?->getStatusCode() ?? 'inconnu';
            throw new RuntimeException("Échec de livraison d’une notification push (HTTP {$status}).");
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Échec définitif de livraison d’une notification push.', [
            'subscription_id' => $this->subscriptionId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
