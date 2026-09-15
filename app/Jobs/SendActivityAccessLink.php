<?php

namespace App\Jobs;

use App\Models\ActivityRegistration;
use App\Services\MessagingService;
use App\Support\SchoolContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SendActivityAccessLink implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $registrationId, public int $version) {}

    /**
     * Execute the job.
     */
    public function handle(MessagingService $messaging): void
    {
        $context = app(SchoolContext::class);
        $previousSchool = $context->school();
        $context->clear();
        try {
            $this->deliver($messaging);
        } finally {
            $previousSchool ? $context->set($previousSchool) : $context->clear();
        }
    }

    private function deliver(MessagingService $messaging): void
    {
        $token = Str::random(64);
        $registration = DB::transaction(function () use ($token): ?ActivityRegistration {
            $registration = ActivityRegistration::lockForUpdate()->find($this->registrationId);
            if (! $registration || $registration->access_version !== $this->version || $registration->delivery_status !== 'pending'
                || $registration->status !== 'active' || $registration->entry?->status !== 'active'
                || ! $registration->activity || $registration->activity->approval_status !== 'approved'
                || $registration->activity->status === 'cancelled' || now()->gte($registration->activity->end_at)) {
                return null;
            }
            $registration->forceFill(['delivery_status' => 'processing', 'token_hash' => hash('sha256', $token)])->save();

            return $registration;
        });
        if (! $registration) {
            return;
        }
        try {
            $messaging->send($registration->channel, $registration->destination,
                'Pendaftaran '.$registration->name.' pada '.$registration->activity->title.' berhasil. Akses '.($registration->role === 'coach' ? 'pembina' : 'siswa').': '
                .route('activity-access.open', ['token' => $token]).' Berlaku '.$registration->activity->start_at->format('d-m-Y H:i').' sampai '.$registration->activity->end_at->format('d-m-Y H:i').' ('.config('app.timezone').'). Jangan bagikan link ini.',
                'Akses peserta kegiatan');
            $status = 'sent';
        } catch (Throwable) {
            $status = 'failed';
        }
        DB::transaction(function () use ($status): void {
            $registration = ActivityRegistration::lockForUpdate()->find($this->registrationId);
            if ($registration && $registration->access_version === $this->version && $registration->delivery_status === 'processing') {
                $registration->forceFill(['delivery_status' => $status, 'sent_at' => $status === 'sent' ? now() : null])->save();
            }
        });
    }
}
