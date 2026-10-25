<?php

use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Middleware\EnsureApiAccountActive;
use App\Http\Middleware\ResolveApiSchool;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('auth.register');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');

    Route::middleware(['auth:sanctum', EnsureApiAccountActive::class])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::get('/dashboard/global', [DashboardController::class, 'global'])->middleware('permission:dashboard.view')->name('dashboard.global');

        Route::middleware(ResolveApiSchool::class)->group(function (): void {
            Route::get('/dashboard', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
            Route::get('/activities', [ActivityController::class, 'index'])->middleware('permission:activities.view')->name('activities.index');
            Route::get('/activities/{activityId}', [ActivityController::class, 'show'])->whereNumber('activityId')->middleware('permission:activities.view')->name('activities.show');
            Route::get('/activities/{activityId}/attendance-sessions', [AttendanceController::class, 'sessions'])->whereNumber('activityId')->middleware('permission:attendance_sessions.view')->name('activities.attendance-sessions');
            Route::get('/attendance-sessions/{sessionId}/participants', [AttendanceController::class, 'participants'])->whereNumber('sessionId')->middleware('permission:attendance_sessions.view')->name('attendance-sessions.participants');
            Route::patch('/attendance-sessions/{sessionId}/participants/{studentId}', [AttendanceController::class, 'mark'])->whereNumber('sessionId')->whereNumber('studentId')->middleware('permission:attendance.manual')->name('attendance-sessions.mark');
            Route::post('/attendance-sessions/{sessionId}/check-in', [AttendanceController::class, 'checkIn'])->whereNumber('sessionId')->middleware('permission:attendances.self')->name('attendance-sessions.check-in');
        });
    });
});
