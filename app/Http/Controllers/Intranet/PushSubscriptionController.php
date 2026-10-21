<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PushSubscriptionController extends Controller
{
    public function publicKey(): JsonResponse
    {
        abort_unless(config('webpush.enabled'), 503, 'Les notifications push ne sont pas configurées.');

        return response()->json(['publicKey' => config('webpush.public_key')]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(config('webpush.enabled'), 503, 'Les notifications push ne sont pas configurées.');

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:4096'],
            'keys.p256dh' => ['required', 'string', 'min:40', 'max:128', 'regex:/\A[A-Za-z0-9_-]+\z/'],
            'keys.auth' => ['required', 'string', 'min:16', 'max:64', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ]);
        $this->validateEndpoint($data['endpoint']);

        PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ]
        );

        return response()->json(['subscribed' => true], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless(config('webpush.enabled'), 503, 'Les notifications push ne sont pas configurées.');

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:4096'],
        ]);
        $this->validateEndpoint($data['endpoint']);

        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json([], 204);
    }

    private function validateEndpoint(string $endpoint): void
    {
        $parts = parse_url($endpoint);

        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || ! isset($parts['host'])) {
            throw ValidationException::withMessages([
                'endpoint' => 'Le fournisseur de notification push n’est pas autorisé.',
            ]);
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        $allowed = collect(config('webpush.allowed_hosts'))->contains(
            static fn (string $allowedHost): bool => $host === $allowedHost
                || str_ends_with($host, '.'.$allowedHost)
        );

        if (! $allowed) {
            throw ValidationException::withMessages([
                'endpoint' => 'Le fournisseur de notification push n’est pas autorisé.',
            ]);
        }
    }
}
