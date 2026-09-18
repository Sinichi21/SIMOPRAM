<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ActivityLogger
{
    private bool $tableAvailable = false;

    public function moduleFor(string $name): string
    {
        if (str_starts_with($name, 'assessments.scores') || str_starts_with($name, 'assessments.grade')) {
            return 'grades';
        }
        foreach (['user-approvals' => 'users', 'school-registrations' => 'schools', 'auth' => 'users', 'notification-settings' => 'messaging'] as $prefix => $module) {
            if (str_starts_with($name, $prefix)) {
                return $module;
            }
        }
        $prefix = explode('.', $name)[0];

        return array_key_exists($prefix, config('activity-log.modules')) ? $prefix : 'settings';
    }

    public function failure(\Throwable $exception, ?string $module = null): void
    {
        if (request()->attributes->get('activity_failure_logged')) {
            return;
        }
        request()->attributes->set('activity_failure_logged', true);
        $security = $exception instanceof AuthorizationException
            || $exception instanceof AuthenticationException
            || $exception instanceof TokenMismatchException
            || $exception instanceof HttpExceptionInterface && in_array($exception->getStatusCode(), [401, 403, 419, 429], true);
        try {
            $this->record($module ?? ($security ? 'users' : $this->moduleFor(request()->route()?->getName() ?? '')), 'failed', new: ['exception_type' => class_basename($exception)], status: 'failed',
                type: $security ? 'security' : ($exception instanceof ValidationException ? 'audit' : 'system'), description: $security ? 'Akses ditolak' : 'Operasi aplikasi gagal');
        } catch (\Throwable) {
            error_log('SIMPRAM: pencatatan kegagalan aktivitas tidak tersedia.');
        }
    }

    public function requestId(): string
    {
        if (! Context::hasHidden('activity_request_id')) {
            Context::addHidden('activity_request_id', 'req_'.Str::uuid());
        }

        return Context::getHidden('activity_request_id');
    }

    /** @return array{id: ?int, name: string, role: string} */
    public function actor(?User $user = null): array
    {
        $user ??= auth()->user();
        if ($user) {
            return ['id' => $user->id, 'name' => $user->name, 'role' => $user->system_role ?? 'guest'];
        }

        return Context::getHidden('activity_actor', ['id' => null, 'name' => app()->runningInConsole() ? 'Sistem' : 'Tamu', 'role' => app()->runningInConsole() ? 'system' : 'guest']);
    }

    /** @param array<string, mixed>|null $values
     * @return array<string, mixed>|null
     */
    public function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $clean = [];
        foreach (array_slice($values, 0, 100, true) as $key => $value) {
            if (! in_array($key, config('activity-log.safe_fields'), true)
                || preg_match('/password|secret|token|cookie|authorization|private.?key|api.?key|session/i', (string) $key) && ! in_array($key, ['attendance_session_id', 'session_date'], true)) {
                $clean[$key] = '[DISEMBUNYIKAN]';
            } elseif (is_array($value)) {
                $clean[$key] = in_array($key, ['role_ids', 'permission_ids'], true)
                    ? array_values(array_filter($value, 'is_int'))
                    : $this->sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = is_string($value) ? Str::limit($value, 300) : $value;
            } else {
                $clean[$key] = '[DISEMBUNYIKAN]';
            }
        }

        return $clean;
    }

    public function userAgent(): ?string
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }
        $agent = request()->userAgent() ?? '';
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge', str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox', str_contains($agent, 'Safari/') => 'Safari',
            default => 'Lainnya',
        };
        $platform = match (true) {
            str_contains($agent, 'Android') => 'Android', str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows', str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux', default => 'Lainnya',
        };

        return $browser.' / '.$platform;
    }

    /** @param array<string, mixed>|null $old
     * @param  array<string, mixed>|null  $new
     */
    public function record(string $module, string $action, ?Model $target = null, ?array $old = null, ?array $new = null, string $status = 'success', string $type = 'audit', ?User $actor = null, ?string $description = null): ?ActivityLog
    {
        if (! array_key_exists($module, config('activity-log.modules')) || ! array_key_exists($action, config('activity-log.actions'))
            || ! in_array($status, ['success', 'failed'], true) || ! in_array($type, ['audit', 'security', 'system'], true)) {
            throw new \InvalidArgumentException('Kategori log aktivitas tidak valid.');
        }

        if (! $this->tableAvailable) {
            $this->tableAvailable = Schema::hasTable('activity_logs');
            if (! $this->tableAvailable) {
                return null;
            }
        }

        $identity = $this->actor($actor);
        $schoolId = match (true) {
            $target instanceof School => $target->id,
            $target !== null && array_key_exists('school_id', $target->getAttributes()) => $target->getAttribute('school_id'),
            $target !== null && in_array(class_basename($target), ['MessagingSetting', 'LandingPageSetting', 'SchoolRegistrationRequest'], true) => null,
            default => app(SchoolContext::class)->id() ?? Context::getHidden('activity_school_id'),
        };
        $schoolName = $schoolId ? School::withTrashed()->whereKey($schoolId)->value('name') : null;
        $ip = request()->ip();
        $time = now();

        return ActivityLog::query()->create([
            'occurred_at' => $time, 'created_at' => $time,
            'log_type' => $type, 'user_id' => $identity['id'], 'user_name' => Str::limit($identity['name'], 250),
            'role' => $identity['role'], 'school_id' => $schoolId, 'school_name' => $schoolName,
            'module' => $module, 'action' => $action,
            'target_type' => $target ? class_basename($target) : null, 'target_id' => $target ? (string) $target->getKey() : null,
            'description' => Str::limit($description ?? config('activity-log.actions.'.$action).' '.config('activity-log.modules.'.$module), 495),
            'old_values' => $action === 'password_changed' ? null : $this->sanitize($old),
            'new_values' => $action === 'password_changed' ? null : $this->sanitize($new),
            'ip_address' => filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
            'user_agent' => $this->userAgent(), 'status' => $status, 'request_id' => $this->requestId(),
        ]);
    }
}
