<?php

namespace App\Jobs;

use App\Messaging\TextMessage;
use App\Models\ActivityMessageDelivery;
use App\Services\MessagingService;
use App\Support\SchoolContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendActivityNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $deliveryId) {}

    /**
     * Execute the job.
     */
    public function handle(MessagingService $messaging): void
    {
        $context = app(SchoolContext::class);
        $previous = $context->school();
        $context->clear();
        try {
            $claimed = ActivityMessageDelivery::query()->whereKey($this->deliveryId)
                ->where('status', 'pending')->update(['status' => 'processing']);
            if (! $claimed) {
                return;
            }
            $delivery = ActivityMessageDelivery::with('registration.entry', 'registration.activity')->findOrFail($this->deliveryId);
            $registration = $delivery->registration;
            $activity = $registration?->activity;
            if (! $registration || $registration->status !== 'active' || $registration->entry?->status !== 'active'
                || $registration->entry->activity_id !== $registration->activity_id || ! $activity
                || $activity->approval_status !== 'approved' || $activity->status === 'draft'
                || ! in_array($delivery->channel, ['email', 'whatsapp'], true)
                || $registration->channel !== $delivery->channel || ! $messaging->enabled($delivery->channel)) {
                $delivery->update(['status' => 'skipped']);

                return;
            }
            try {
                $messaging->sendMessage($delivery->channel, $registration->destination,
                    new TextMessage('Halo '.$registration->name.",\n\n".$delivery->body, $delivery->title));
                $delivery->update(['status' => 'sent', 'sent_at' => now()]);
            } catch (Throwable) {
                $delivery->update(['status' => 'failed']);
            }
        } finally {
            $previous ? $context->set($previous) : $context->clear();
        }
    }
}
