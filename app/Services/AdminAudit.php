<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AdminAudit
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'remember_token', 'ldap_password',
        'secret', 'token', 'api_key', 'private_key',
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(
        string $action,
        string $category,
        ?Model $target = null,
        ?array $before = null,
        ?array $after = null,
        ?string $targetLabel = null,
    ): ?AdminAuditLog {
        if (! Schema::hasTable('admin_audit_logs')) {
            return null;
        }

        $request = app()->runningInConsole() ? null : request();

        return AdminAuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'category' => $category,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'target_label' => $targetLabel ?? self::label($target),
            'before' => self::sanitize($before),
            'after' => self::sanitize($after),
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 1000),
        ]);
    }

    private static function label(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        return (string) ($model->name ?? $model->nom ?? $model->title ?? $model->subject ?? class_basename($model).' #'.$model->getKey());
    }

    /** @param array<string, mixed>|null $data */
    private static function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return collect($data)->mapWithKeys(function ($value, $key): array {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, self::SENSITIVE_KEYS, true)
                || str_contains($normalized, 'password')
                || str_contains($normalized, 'secret')) {
                return [$key => '[masqué]'];
            }

            if (is_array($value)) {
                return [$key => self::sanitize($value)];
            }

            return [$key => $value instanceof \BackedEnum ? $value->value : $value];
        })->all();
    }
}
