<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ActivityLogRecorder;
use App\Support\SchoolContext;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleComponents\ComponentContext;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Throwable;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->scoped(ActivityLogger::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Gate::define('activity-logs.view', fn (User $user): bool => $user->isSuperAdmin());

        foreach (['created', 'updated', 'deleted', 'restored'] as $action) {
            Event::listen('eloquent.'.$action.': *', function (string $event, array $models) use ($action): void {
                app(ActivityLogRecorder::class)->model($action, $models[0]);
            });
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                app(ActivityLogger::class)->record('users', 'login', $event->user, type: 'security', actor: $event->user);
            }
        });
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                app(ActivityLogger::class)->record('users', 'logout', $event->user, type: 'security', actor: $event->user);
            }
        });
        Event::listen(Failed::class, function (Failed $event): void {
            $user = $event->user instanceof User ? $event->user : null;
            app(ActivityLogger::class)->record('users', 'login', $user, status: 'failed', type: 'security', actor: $user, description: 'Login gagal');
        });
        Event::listen(Lockout::class, function (): void {
            app(ActivityLogger::class)->record('users', 'failed', status: 'failed', type: 'security', description: 'Batas percobaan login terlampaui');
        });

        Event::listen(NotificationSent::class, function (): void {
            app(ActivityLogger::class)->record('messaging', 'sent', description: 'Mengirim notifikasi aplikasi');
        });

        foreach ([RoleAttachedEvent::class, RoleDetachedEvent::class, PermissionAttachedEvent::class, PermissionDetachedEvent::class] as $eventClass) {
            Event::listen($eventClass, function (RoleAttachedEvent|RoleDetachedEvent|PermissionAttachedEvent|PermissionDetachedEvent $event): void {
                $attached = $event instanceof RoleAttachedEvent || $event instanceof PermissionAttachedEvent;
                $roles = $event instanceof RoleAttachedEvent || $event instanceof RoleDetachedEvent;
                $ids = $this->permissionIds($roles ? $event->rolesOrIds : $event->permissionsOrIds);
                $values = [$roles ? 'role_ids' : 'permission_ids' => $ids];
                app(ActivityLogger::class)->record('roles', 'updated', $event->model, $attached ? null : $values, $attached ? $values : null,
                    type: 'security', description: ($attached ? 'Menambahkan' : 'Melepas').' hak akses pengguna');
            });
        }

        Livewire::listen('dehydrate', function (Component $component, ComponentContext $context): void {
            if (\Livewire\store($component)->has('download')) {
                $module = match (true) {
                    str_starts_with($component::class, 'App\\Livewire\\Reports\\') => 'reports',
                    str_starts_with($component::class, 'App\\Livewire\\Students\\') => 'students',
                    default => 'archives',
                };
                app(ActivityLogger::class)->record($module, $module === 'reports' ? 'exported' : 'downloaded');
            }
        });
        Livewire::listen('exception', function (object $component, Throwable $exception): void {
            $logger = app(ActivityLogger::class);
            $name = implode('.', array_map(fn (string $part): string => Str::kebab($part), explode('\\', Str::after($component::class, 'App\\Livewire\\'))));
            $logger->failure($exception, $logger->moduleFor($name));
        });

        Queue::createPayloadUsing(function (): array {
            $logger = app(ActivityLogger::class);

            return ['activity_context' => ['actor' => $logger->actor(), 'request_id' => $logger->requestId(), 'school_id' => app(SchoolContext::class)->id()]];
        });
        Queue::before(function (JobProcessing $event): void {
            request()->attributes->remove('activity_failure_logged');
            $context = $event->job->payload()['activity_context'] ?? [];
            Context::addHidden('activity_actor', $context['actor'] ?? ['id' => null, 'name' => 'Sistem', 'role' => 'system']);
            Context::addHidden('activity_request_id', $context['request_id'] ?? 'req_'.Str::uuid());
            Context::addHidden('activity_school_id', $context['school_id'] ?? null);
        });
        Queue::failing(function (JobFailed $event): void {
            app(ActivityLogger::class)->failure($event->exception);
        });
    }

    /** @return list<int> */
    private function permissionIds(mixed $values): array
    {
        if ($values instanceof Collection) {
            $values = $values->all();
        }
        $ids = [];
        foreach (is_array($values) ? $values : [$values] as $value) {
            $id = $value instanceof Model ? $value->getKey() : $value;
            if (is_int($id) || is_string($id) && ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }
}
