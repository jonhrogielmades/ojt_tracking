<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $event,
        ?User $actor = null,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): AuditLog {
        $request = app()->runningInConsole() ? null : request();

        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request === null ? null : Str::limit((string) $request->userAgent(), 500, ''),
            'old_values' => $this->sanitize($oldValues),
            'new_values' => $this->sanitize($newValues),
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    public function identifierFingerprint(string $identifier): string
    {
        return hash_hmac('sha256', Str::lower(trim($identifier)), (string) config('app.key'));
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        return Arr::map($values, function (mixed $value, string|int $key): mixed {
            if (is_string($key) && preg_match('/password|token|secret|credential|code|hash|remember/i', $key) === 1) {
                return '[REDACTED]';
            }

            if (is_array($value)) {
                return $this->sanitize($value);
            }

            if ($value instanceof BackedEnum) {
                return $value->value;
            }

            if ($value instanceof DateTimeInterface) {
                return $value->format(DATE_ATOM);
            }

            if (is_scalar($value) || $value === null) {
                return $value;
            }

            return $value instanceof Stringable ? (string) $value : get_debug_type($value);
        });
    }
}
