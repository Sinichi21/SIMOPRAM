<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ActivityEntryService
{
    public const CATEGORIES = ['individual' => 'Individu', 'siaga' => 'Barung (Siaga)', 'penggalang' => 'Regu (Penggalang)', 'penegak' => 'Sangga (Penegak)', 'team' => 'Tim / Grup'];

    public const DEFAULT_TERMS = 'Peserta wajib mengikuti ketentuan dan tata tertib kegiatan serta arahan panitia dan pembina pendamping.';

    /** @return array{0:int, 1:int} */
    public function limits(Activity $activity, string $category): array
    {
        return match ($category) {
            'siaga' => [4, 6], 'penggalang' => [6, 8], 'penegak' => [4, 8],
            'team' => [(int) ($activity->team_min ?? 1), (int) ($activity->team_max ?? 20)],
            default => [1, 1],
        };
    }

    /** @param array<string, mixed> $data
     * @param  array<int, UploadedFile>  $files
     */
    public function save(Activity $activity, array $data, array $files, ?User $actor = null, ?ActivityEntry $entry = null, bool $managed = false): ActivityEntry
    {
        if ($managed) {
            app(GlobalActivityAccess::class)->authorize($activity);
        } else {
            app(ActivityRegistrationService::class)->assertRegistrationOpen($activity);
        }
        abort_if($entry && $entry->activity_id !== $activity->id, 404);
        $fields = $entry?->form_snapshot ?? $activity->registration_fields ?? [];
        $categories = $activity->registration_categories ?: ['individual'];
        $base = validator($data, [
            'category' => ['required', Rule::in($categories)], 'name' => ['nullable', 'string', 'max:150'],
            'members' => ['required', 'array', 'min:1', 'max:100'], 'members.*' => ['required', 'array'],
            'coach' => ['required', 'array'], 'reserve' => ['nullable', 'array'],
            'declaration' => ['accepted'], 'terms' => ['accepted'], 'answers' => ['nullable', 'array'],
        ], ['declaration.accepted' => 'Pernyataan keaslian dan pertanggungjawaban data wajib disetujui.', 'terms.accepted' => 'Syarat dan ketentuan wajib disetujui.'])->validate();
        [$minimum, $maximum] = $this->limits($activity, $base['category']);
        validator($base, ['members' => ['array', 'min:'.$minimum, 'max:'.$maximum], 'name' => [$base['category'] === 'individual' ? 'nullable' : 'required']])->validate();
        $answerRules = [];
        $answerAttributes = [];
        $fileField = null;
        foreach ($fields as $field) {
            $answerAttributes['answers.'.$field['id']] = $field['label'];
            $answerAttributes['answers.'.$field['id'].'.*'] = $field['label'];
            if ($field['type'] === 'file') {
                $fileField = $field;

                continue;
            }
            $rules = [$field['required'] ? 'required' : 'nullable'];
            $answerRules['answers.'.$field['id']] = [...$rules, ...match ($field['type']) {
                'checkbox' => ['array', 'max:'.count($field['options'])],
                'radio', 'select' => ['string', Rule::in($field['options'])],
                'date' => ['date_format:Y-m-d'], 'time' => ['date_format:H:i'],
                'paragraph' => ['string', 'max:10000'], default => ['string', 'max:500'],
            }];
            if ($field['type'] === 'checkbox') {
                $answerRules['answers.'.$field['id'].'.*'] = ['string', 'distinct', Rule::in($field['options'])];
            }
        }
        $allowedIds = collect($fields)->where('type', '!=', 'file')->pluck('id')->all();
        $incomingAnswers = $base['answers'] ?? [];
        if (array_diff(array_keys($incomingAnswers), $allowedIds)) {
            throw ValidationException::withMessages(['answers' => 'Formulir berubah atau berisi field yang tidak dikenal. Muat ulang formulir.']);
        }
        $answers = validator(['answers' => $incomingAnswers], $answerRules, [], $answerAttributes)->validate()['answers'] ?? [];
        validator(['files' => $files], [
            'files' => [$fileField ? 'array' : 'prohibited', 'max:10'],
            'files.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ])->validate();
        if ($fileField && $fileField['required'] && count($files) === 0 && empty($entry?->attachments)) {
            throw ValidationException::withMessages(['files' => 'Lampiran wajib diunggah.']);
        }
        $stored = [];
        try {
            foreach ($files as $file) {
                $path = $file->store('activity-registrations', 'local');
                if (! $path) {
                    throw ValidationException::withMessages(['files' => 'Lampiran gagal disimpan. Silakan coba kembali.']);
                }
                $stored[] = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType(), 'size' => $file->getSize()];
            }

            return DB::transaction(function () use ($activity, $base, $fields, $answers, $actor, $entry, $managed, $stored): ActivityEntry {
                $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->lockForUpdate()->findOrFail($activity->id);
                if ($managed) {
                    app(GlobalActivityAccess::class)->authorize($activity);
                } else {
                    app(ActivityRegistrationService::class)->assertRegistrationOpen($activity);
                    if (($activity->registration_fields ?? []) !== $fields) {
                        throw ValidationException::withMessages(['answers' => 'Formulir berubah. Muat ulang sebelum mendaftar.']);
                    }
                }
                $entry = $entry ? ActivityEntry::where('activity_id', $activity->id)->lockForUpdate()->findOrFail($entry->id) : new ActivityEntry;
                $entry->fill(['activity_id' => $activity->id, 'name' => trim($base['name'] ?? '') ?: 'Peserta individu', 'category' => $base['category'],
                    'answers' => $answers, 'form_snapshot' => $fields, 'terms_snapshot' => $entry->terms_snapshot ?? $activity->registration_terms ?? self::DEFAULT_TERMS,
                    'declaration_accepted_at' => now(), 'terms_accepted_at' => now(), 'validation_status' => 'pending', 'validated_by' => null, 'validated_at' => null]);
                if (! $entry->exists) {
                    $entry->registered_by = $actor?->id;
                }
                if ($stored !== []) {
                    $oldAttachments = $entry->attachments ?? [];
                    $entry->attachments = $stored;
                    DB::afterCommit(function () use ($oldAttachments): void {
                        foreach ($oldAttachments as $file) {
                            Storage::disk('local')->delete($file['path']);
                        }
                    });
                }
                $entry->save();
                $ids = [];
                $members = collect($base['members'])->map(fn (array $member): array => [...$member, 'role' => 'student', 'is_reserve' => false]);
                $members->push([...$base['coach'], 'role' => 'coach', 'is_reserve' => false]);
                if (! empty($base['reserve'])) {
                    $members->push([...$base['reserve'], 'role' => 'student', 'is_reserve' => true]);
                }
                foreach ($members as $member) {
                    if (($member['source'] ?? '') !== 'external' && ($member['source'] ?? '') !== $member['role']) {
                        throw ValidationException::withMessages(['members' => 'Data SIMPRAM harus sesuai role siswa atau pembina.']);
                    }
                    $registration = app(ActivityRegistrationService::class)->register($activity, $entry, $member, $actor, $managed, $member['is_reserve']);
                    if (in_array($registration->id, $ids, true)) {
                        throw ValidationException::withMessages(['members' => 'Anggota, pembina, dan cadangan tidak boleh didaftarkan berulang dalam satu peserta.']);
                    }
                    $ids[] = $registration->id;
                }
                foreach ($entry->members()->whereNotIn('id', $ids)->get() as $removed) {
                    $removed->forceFill(['status' => 'revoked', 'token_hash' => null, 'access_version' => $removed->access_version + 1])->save();
                }
                if ($entry->category === 'individual') {
                    $entry->update(['name' => $entry->members()->findOrFail($ids[0])->name]);
                }

                return $entry;
            });
        } catch (Throwable $exception) {
            foreach ($stored as $file) {
                Storage::disk('local')->delete($file['path']);
            }
            throw $exception;
        }
    }
}
