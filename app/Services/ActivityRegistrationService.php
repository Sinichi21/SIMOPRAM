<?php

namespace App\Services;

use App\Jobs\SendActivityAccessLink;
use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Models\ActivityRegistration;
use App\Models\Coach;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityRegistrationService
{
    public function assertRegistrationOpen(Activity $activity): void
    {
        abort_unless($activity->school_id === null && $activity->approval_status === 'approved' && $activity->registration_open
            && $activity->is_public && in_array($activity->status, ['published', 'ongoing'], true)
            && (! $activity->published_at || $activity->published_at->lte(now())) && $activity->end_at->gt(now()), 403, 'Pendaftaran kegiatan tidak tersedia.');
    }

    /** @param array<string, mixed> $data */
    public function register(Activity $activity, ActivityEntry $entry, array $data, ?User $actor = null, bool $managed = false, bool $reserve = false): ActivityRegistration
    {
        if ($managed) {
            app(GlobalActivityAccess::class)->authorize($activity);
        } else {
            $this->assertRegistrationOpen($activity);
        }
        $data = validator($data, [
            'source' => ['required', 'in:external,student,coach'], 'profile_id' => ['required_if:source,student,coach', 'nullable', 'integer'],
            'name' => ['required_if:source,external', 'nullable', 'string', 'max:150'],
            'identifier' => ['nullable', 'string', 'max:50'], 'school_name' => ['nullable', 'string', 'max:200'],
            'role' => ['required', 'in:student,coach'], 'channel' => ['required', 'in:email,whatsapp'],
            'destination' => ['required_if:source,external', 'nullable', 'string', 'max:255'],
        ], [
            'profile_id.required_if' => 'Pilih data siswa atau pembina SIMPRAM terlebih dahulu.',
            'name.required_if' => 'Nama peserta atau pembina wajib diisi.',
            'destination.required_if' => 'Email atau nomor WhatsApp penerima akses wajib diisi.',
            'channel.required' => 'Pilih metode pengiriman akses.',
        ])->validate();
        $profile = null;
        $originSchoolId = null;
        $userId = null;
        if ($data['source'] !== 'external') {
            abort_unless($managed || $actor?->is_active, 403);
            $query = $data['source'] === 'student' ? Student::withoutGlobalScope('school')->where('status', 'active') : Coach::withoutGlobalScope('school')->where('is_active', true);
            if (! $managed) {
                $schoolIds = app(GlobalActivityAccess::class)->schoolAdminIds($actor);
                $query->where(fn ($query) => $query->where('user_id', $actor->id)->orWhereIn('school_id', $schoolIds));
            }
            $profile = $query->with('school', 'user')->findOrFail($data['profile_id'] ?? 0);
            abort_unless($profile->school?->is_active, 403);
            if ($profile->user_id) {
                abort_unless($profile->user?->is_active && $profile->user->schoolMemberships()->where('school_id', $profile->school_id)->where('is_active', true)->whereNull('left_at')->exists(), 403);
            }
            $data['name'] = $profile->name;
            $data['role'] = $data['source'];
            $data['school_name'] = $profile->school->name;
            $data['identifier'] = $data['source'] === 'coach' ? ($profile->nip ?: ($data['identifier'] ?? null)) : ($data['identifier'] ?? null);
            $storedDestination = $data['channel'] === 'email' ? $profile->user?->email : ($profile->phone ?: ($profile instanceof Student ? $profile->parent_phone : null) ?: $profile->user?->phone);
            $data['destination'] = $storedDestination ?: ($data['destination'] ?? null);
            $originSchoolId = $profile->school_id;
            $userId = $profile->user_id;
        }
        try {
            $destination = app(MessagingService::class)->destination($data['channel'], (string) ($data['destination'] ?? ''));
        } catch (ValidationException $exception) {
            $contact = $data['channel'] === 'email' ? 'email' : 'nomor WhatsApp';
            throw ValidationException::withMessages(['destination' => 'Isi '.$contact.' yang valid untuk '.$data['name'].'. Jika kontak belum tersedia di SIMPRAM, isi kontak penerima akses pada form registrasi.']);
        }
        $identity = $profile ? $data['source'].':'.$profile->id : 'external:'.mb_strtolower(trim($data['name'])).':'.mb_strtolower(trim($data['school_name'] ?? '')).':'.trim($data['identifier'] ?? '');

        return DB::transaction(function () use ($activity, $entry, $data, $destination, $identity, $profile, $originSchoolId, $userId, $managed, $reserve): ActivityRegistration {
            $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->lockForUpdate()->findOrFail($activity->id);
            if (! $managed) {
                $this->assertRegistrationOpen($activity);
            }
            $identityKey = hash('sha256', $identity);
            if ($data['role'] === 'student' && $activity->registrations()->where('identity_key', $identityKey)->where('entry_id', '!=', $entry->id)->where('status', 'active')->whereHas('entry', fn ($query) => $query->where('status', 'active'))->exists()) {
                throw ValidationException::withMessages(['registration' => 'Peserta sudah terdaftar. Hubungi pengelola jika membutuhkan pengiriman ulang akses.']);
            }
            $registration = ActivityRegistration::firstOrNew(['entry_id' => $entry->id, 'identity_key' => $identityKey]);
            $send = ! $registration->exists || $registration->status !== 'active' || $registration->destination !== $destination;
            $registration->fill([
                'activity_id' => $activity->id, 'entry_id' => $entry->id, 'is_reserve' => $reserve, 'status' => 'active',
                'user_id' => $userId, 'student_id' => $data['source'] === 'student' ? $profile?->id : null,
                'coach_id' => $data['source'] === 'coach' ? $profile?->id : null,
                'origin_school_id' => $originSchoolId, 'name' => trim($data['name']), 'identifier' => $data['identifier'] ?? null,
                'school_name' => $data['school_name'] ?? null, 'role' => $data['role'], 'channel' => $data['channel'],
                'destination' => $destination, 'identity_key' => $identityKey,
            ]);
            if ($send) {
                $registration->forceFill(['token_hash' => null, 'access_version' => $registration->exists ? $registration->access_version + 1 : 1,
                    'delivery_status' => 'pending', 'link_requested_at' => now()]);
            }
            $registration->save();
            if ($send) {
                SendActivityAccessLink::dispatch($registration->id, $registration->access_version)->afterCommit();
            }

            return $registration;
        });
    }

    public function resend(ActivityRegistration $registration): void
    {
        app(GlobalActivityAccess::class)->authorize($registration->activity);
        DB::transaction(function () use ($registration): void {
            $registration = ActivityRegistration::lockForUpdate()->findOrFail($registration->id);
            abort_unless($registration->status === 'active' && $registration->entry?->status === 'active' && $registration->activity->approval_status === 'approved'
                && $registration->activity->status !== 'cancelled' && $registration->activity->end_at->gt(now()), 403);
            if ($registration->link_requested_at?->gt(now()->subMinute())) {
                throw ValidationException::withMessages(['delivery' => 'Tunggu satu menit sebelum mengirim ulang.']);
            }
            $registration->forceFill(['token_hash' => null, 'access_version' => $registration->access_version + 1,
                'delivery_status' => 'pending', 'sent_at' => null, 'link_requested_at' => now()])->save();
            SendActivityAccessLink::dispatch($registration->id, $registration->access_version)->afterCommit();
        });
    }

    public function revoke(ActivityRegistration $registration): void
    {
        app(GlobalActivityAccess::class)->authorize($registration->activity);
        DB::transaction(function () use ($registration): void {
            $registration = ActivityRegistration::lockForUpdate()->findOrFail($registration->id);
            $registration->forceFill(['status' => 'revoked', 'token_hash' => null, 'access_version' => $registration->access_version + 1])->save();
        });
    }

    public function assertAccessible(ActivityRegistration $registration): void
    {
        $activity = $registration->activity;
        abort_unless($registration->status === 'active' && $registration->entry?->status === 'active' && $activity && $activity->school_id === null
            && $activity->approval_status === 'approved' && in_array($activity->status, ['published', 'ongoing'], true)
            && now()->gte($activity->start_at) && now()->lt($activity->end_at), 410, 'Akses hanya tersedia selama kegiatan berlangsung.');
        if ($registration->user_id) {
            abort_unless(User::whereKey($registration->user_id)->where('is_active', true)->exists(), 410);
        }
    }
}
