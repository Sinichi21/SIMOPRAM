<?php

namespace App\Services;

use App\Jobs\SendActivityNotification;
use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Models\ActivityMessageDelivery;
use App\Models\ActivityRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActivityNotificationService
{
    public function updated(Activity $activity): void
    {
        $labels = [
            'title' => 'Nama kegiatan', 'description' => 'Deskripsi', 'location' => 'Lokasi',
            'start_at' => 'Waktu mulai', 'end_at' => 'Waktu selesai', 'status' => 'Status',
            'attachments' => 'Lampiran', 'banner_path' => 'Banner', 'registration_open' => 'Pendaftaran',
            'registration_fields' => 'Formulir pendaftaran', 'registration_categories' => 'Kategori peserta',
            'registration_terms' => 'Ketentuan pendaftaran', 'team_min' => 'Jumlah minimal anggota',
            'team_max' => 'Jumlah maksimal anggota', 'latitude' => 'Koordinat lokasi', 'longitude' => 'Koordinat lokasi',
        ];
        $changed = array_intersect_key($labels, $activity->getChanges());
        if ($changed === [] || $activity->approval_status !== 'approved' || $activity->status === 'draft') {
            return;
        }
        $status = ['published' => 'Dipublikasikan', 'ongoing' => 'Berlangsung', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
        $body = $activity->title."\n\nPerubahan: ".implode(', ', array_unique($changed)).'.'
            ."\nStatus: ".($status[$activity->status] ?? $activity->status)
            ."\nJadwal: ".$activity->start_at?->format('d-m-Y H:i').' sampai '.$activity->end_at?->format('d-m-Y H:i').' ('.config('app.timezone').')'
            ."\nLokasi: ".($activity->location ?: 'Belum ditentukan');
        if (isset($changed['description'])) {
            $body .= "\n\n".strip_tags($activity->description ?? '');
        }
        if ($activity->school_id === null && $activity->is_public && in_array($activity->status, ['published', 'ongoing', 'completed'], true)
            && (! $activity->published_at || $activity->published_at->lte(now()))) {
            $body .= "\n\nDetail kegiatan: ".route('public.activities.show', $activity->id);
        }
        $this->queue($activity, 'Pembaruan kegiatan: '.$activity->title, $body);
    }

    public function approved(ActivityEntry $entry): void
    {
        if ($entry->wasChanged('validation_status') && $entry->validation_status === 'validated') {
            $this->queue($entry->activity, 'Pendaftaran disetujui',
                'Pendaftaran '.$entry->name.' pada '.$entry->activity->title.' telah divalidasi/disetujui oleh pengelola.', $entry->id);
        }
    }

    public function announce(Activity $activity, string $title, string $body): int
    {
        app(GlobalActivityAccess::class)->authorize($activity);
        abort_unless($activity->approval_status === 'approved' && $activity->status !== 'draft', 409);

        return $this->queue($activity, $title, $activity->title."\n\n".$body);
    }

    public function queue(Activity $activity, string $title, string $body, ?int $entryId = null): int
    {
        return DB::transaction(function () use ($activity, $title, $body, $entryId): int {
            $count = 0;
            ActivityRegistration::query()->where('activity_id', $activity->id)->where('status', 'active')
                ->whereIn('channel', ['email', 'whatsapp'])
                ->whereHas('entry', fn (Builder $query) => $query->where('activity_id', $activity->id)->where('status', 'active'))
                ->when($entryId !== null, fn (Builder $query) => $query->where('entry_id', $entryId))
                ->chunkById(100, function ($registrations) use ($title, $body, &$count): void {
                    foreach ($registrations as $registration) {
                        $delivery = ActivityMessageDelivery::create([
                            'activity_registration_id' => $registration->id, 'channel' => $registration->channel,
                            'title' => mb_substr($title, 0, 255), 'body' => $body, 'status' => 'pending',
                        ]);
                        SendActivityNotification::dispatch($delivery->id)->afterCommit();
                        $count++;
                    }
                });

            return $count;
        });
    }
}
