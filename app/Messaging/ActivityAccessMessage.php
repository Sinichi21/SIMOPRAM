<?php

namespace App\Messaging;

use App\Models\ActivityRegistration;

class ActivityAccessMessage extends TextMessage
{
    public function __construct(private readonly ActivityRegistration $registration, private readonly string $url)
    {
        parent::__construct('Pendaftaran '.$registration->name.' pada '.$registration->activity->title.' berhasil. Akses '.($registration->role === 'coach' ? 'pembina' : 'siswa').': '.$url.' Berlaku '.$registration->activity->start_at->format('d-m-Y H:i').' sampai '.$registration->activity->end_at->format('d-m-Y H:i').' ('.config('app.timezone').'). Jangan bagikan link ini.', 'Akses peserta kegiatan');
    }

    public function emailView(): string
    {
        return 'mail.notification.activity-access';
    }

    /** @return array{name: string, activity: string, startsAt: string, endsAt: string, url: string} */
    public function data(): array
    {
        return ['name' => $this->registration->name, 'activity' => $this->registration->activity->title,
            'startsAt' => $this->registration->activity->start_at->format('d-m-Y H:i'),
            'endsAt' => $this->registration->activity->end_at->format('d-m-Y H:i'), 'url' => $this->url];
    }
}
