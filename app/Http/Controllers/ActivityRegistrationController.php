<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityEntry;
use App\Models\Coach;
use App\Models\Student;
use App\Services\ActivityEntryService;
use App\Services\ActivityRegistrationService;
use App\Services\GlobalActivityAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityRegistrationController extends Controller
{
    private function publicActivity(int $id): Activity
    {
        return Activity::withoutGlobalScope('school')->whereNull('school_id')->where('approval_status', 'approved')->where('is_public', true)
            ->whereIn('status', ['published', 'ongoing', 'completed'])->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))->findOrFail($id);
    }

    public function participants(int $activityId): View
    {
        $activity = $this->publicActivity($activityId);
        $entries = $activity->entries()->where('status', 'active')->where('validation_status', 'validated')
            ->select('id', 'activity_id', 'name', 'category')->with(['members' => fn ($query) => $query->where('status', 'active')
            ->select('id', 'entry_id', 'name', 'identifier', 'school_name', 'role', 'is_reserve')])->orderBy('name')->paginate(15);

        return view('landing.participants', compact('activity', 'entries'));
    }

    public function create(Request $request, int $activityId): View
    {
        $activity = $this->publicActivity($activityId);
        app(ActivityRegistrationService::class)->assertRegistrationOpen($activity);

        return $this->form($request, $activity);
    }

    public function store(Request $request, int $activityId, ActivityEntryService $service): RedirectResponse
    {
        $activity = $this->publicActivity($activityId);
        $service->save($activity, $request->only(['name', 'category', 'members', 'coach', 'reserve', 'answers', 'declaration', 'terms']), $request->file('files', []), $request->user());

        return redirect()->route('public.activities.participants', $activityId)->with('status', 'Pendaftaran berhasil dan menunggu validasi pengelola. Link akses siswa/pembina dijadwalkan untuk dikirim langsung ke kontak masing-masing.');
    }

    public function adminCreate(Request $request, int $activityId): View
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($activityId);
        app(GlobalActivityAccess::class)->authorize($activity);

        return $this->form($request, $activity, managed: true);
    }

    public function edit(Request $request, int $activityId, int $entryId): View
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($activityId);
        app(GlobalActivityAccess::class)->authorize($activity);
        $entry = $activity->entries()->with(['members' => fn ($query) => $query->where('status', 'active')])->findOrFail($entryId);

        return $this->form($request, $activity, $entry, true);
    }

    public function adminStore(Request $request, int $activityId, ActivityEntryService $service, ?int $entryId = null): RedirectResponse
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($activityId);
        app(GlobalActivityAccess::class)->authorize($activity);
        $entry = $entryId ? $activity->entries()->findOrFail($entryId) : null;
        $service->save($activity, $request->only(['name', 'category', 'members', 'coach', 'reserve', 'answers', 'declaration', 'terms']), $request->file('files', []), $request->user(), $entry, true);

        return redirect()->route('admin.activity-participants', $activityId)->with('status', 'Data peserta disimpan dan perlu divalidasi. Akses anggota baru dijadwalkan untuk dikirim.');
    }

    public function attachment(int $activityId, int $entryId, int $index): BinaryFileResponse
    {
        $activity = Activity::withoutGlobalScope('school')->whereNull('school_id')->findOrFail($activityId);
        app(GlobalActivityAccess::class)->authorize($activity);
        $entry = $activity->entries()->findOrFail($entryId);
        $file = ($entry->attachments ?? [])[$index] ?? null;
        abort_unless($file && Storage::disk('local')->exists($file['path']), 404);

        return response()->download(Storage::disk('local')->path($file['path']), $file['name'], ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function form(Request $request, Activity $activity, ?ActivityEntry $entry = null, bool $managed = false): View
    {
        $user = $request->user();
        $schoolIds = $user ? app(GlobalActivityAccess::class)->schoolAdminIds($user) : collect();
        $profiles = [];
        foreach (['student' => Student::class, 'coach' => Coach::class] as $source => $model) {
            $query = $model::withoutGlobalScope('school')->with('school:id,name')->whereHas('school', fn ($query) => $query->where('is_active', true));
            $source === 'student' ? $query->where('status', 'active') : $query->where('is_active', true);
            if (! $managed) {
                $query->where(fn ($query) => $query->where('user_id', $user?->id ?? 0)->orWhereIn('school_id', $schoolIds));
            }
            $profiles[$source] = $query->orderBy('name')->get(['id', 'school_id', 'name'])->map(fn ($profile): array => [
                'id' => $profile->id, 'name' => $profile->name, 'school' => $profile->school?->name,
            ])->all();
        }
        $memberData = fn ($member): array => ['source' => $member->student_id ? 'student' : ($member->coach_id ? 'coach' : 'external'),
            'profile_id' => $member->student_id ?? $member->coach_id ?? '', 'name' => $member->name, 'identifier' => $member->identifier ?? '',
            'school_name' => $member->school_name ?? '', 'channel' => $member->channel, 'destination' => $member->destination];
        $blank = ['source' => 'external', 'profile_id' => '', 'name' => '', 'identifier' => '', 'school_name' => '', 'channel' => 'email', 'destination' => ''];
        $formData = [
            'category' => old('category', $entry?->category ?? ($activity->registration_categories[0] ?? 'individual')),
            'members' => old('members', $entry ? $entry->members->where('role', 'student')->where('is_reserve', false)->map($memberData)->values()->all() : [$blank]),
            'coach' => old('coach', ($coach = $entry?->members->firstWhere('role', 'coach')) ? $memberData($coach) : $blank),
            'reserve' => old('reserve', ($reserve = $entry?->members->firstWhere('is_reserve', true)) ? $memberData($reserve) : $blank),
            'hasReserve' => old('reserve') !== null || (bool) $entry?->members->firstWhere('is_reserve', true),
        ];
        $fields = $entry?->form_snapshot ?? $activity->registration_fields ?? [];

        return view('landing.registration', compact('activity', 'entry', 'managed', 'profiles', 'formData', 'blank', 'fields'));
    }
}
