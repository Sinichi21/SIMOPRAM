<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAssessment;
use App\Models\Announcement;
use App\Models\LandingPageSetting;
use App\Models\School;
use App\Services\ActivityJudgeService;
use App\Services\PublicAssessmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class LandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $schools = School::query()
            ->where('is_active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('npsn', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(8)
            ->withQueryString();

        $content = LandingPageSetting::contentForPage();
        $heroImage = LandingPageSetting::where('key', 'global')->value('hero_image');
        $announcements = $this->publicAnnouncements(Announcement::query()->whereNull('school_id'))->latest('published_at')->limit(6)->get();
        $activities = $this->publicActivities(Activity::query()->whereNull('school_id'))->latest('start_at')->limit(6)->get();

        return view('landing.index', compact('schools', 'search', 'content', 'heroImage', 'announcements', 'activities'));
    }

    // public function school(School $school): View
    // {
    //     abort_unless($school->is_active, 404);

    //     $school->load([
    //         'announcements' => fn ($query) => $this->publicAnnouncements($query->getQuery())
    //             ->latest('published_at')->limit(3),
    //         'activities' => fn ($query) => $this->publicActivities($query->getQuery())
    //             ->latest('start_at')->limit(6),
    //         'coaches' => fn ($query) => $query->withoutGlobalScope('school')->with('user:id,email')->where('is_active', true)->orderBy('name')->limit(3),
    //     ]);

    //     return view('landing.school', compact('school'));
    // }

    public function school(School $school): View
    {
        abort_unless($school->is_active, 404);

        $school->load([
            'announcements' => fn ($query) => $this
                ->publicAnnouncements($query->getQuery())
                ->latest('published_at')
                ->limit(3),

            'activities' => function ($query) use ($school): void {
                $activityQuery = $this->publicActivities(
                    $query->getQuery()
                );

                $activityQuery
                    ->with([
                        'journal' => function ($journalQuery) use ($school): void {
                            $journalQuery
                                ->withoutGlobalScope('school')
                                ->where('school_id', $school->id)
                                ->where('status', 'published')
                                ->where(function (Builder $query): void {
                                    $query
                                        ->whereNull('published_at')
                                        ->orWhere('published_at', '<=', now());
                                })
                                ->with([
                                    'attachments' => function ($attachmentQuery) use ($school): void {
                                        $attachmentQuery
                                            ->withoutGlobalScope('school')
                                            ->where('school_id', $school->id)
                                            ->where(
                                                'mime_type',
                                                'like',
                                                'image/%'
                                            )
                                            ->orderBy('id');
                                    },
                                ]);
                        },
                    ])
                    ->latest('start_at')
                    ->limit(6);
            },

            'coaches' => fn ($query) => $query
                ->withoutGlobalScope('school')
                ->with('user:id,email')
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(3),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cover dokumentasi
        |--------------------------------------------------------------------------
        |
        | Foto jurnal hanya menjadi cover apabila jurnal publik tersedia.
        | Activity tanpa jurnal tetap ditampilkan.
        |
        */
        $school->activities->each(function (Activity $activity): void {
            $attachments = $activity->journal?->attachments ?? collect();

            $cover = $attachments->first(
                function ($attachment): bool {
                    if (
                        blank($attachment->path)
                        || ! str_starts_with(
                            (string) $attachment->mime_type,
                            'image/'
                        )
                    ) {
                        return false;
                    }

                    return Storage::disk('public')
                        ->exists($attachment->path);
                }
            );

            $activity->setAttribute(
                'cover_image_url',
                $cover
                    ? Storage::disk('public')->url($cover->path)
                    : null
            );
        });

        return view('landing.school', compact('school'));
    }

    public function announcement(School $school, int $announcementId): View
    {
        abort_unless($school->is_active, 404);
        $announcement = $this->publicAnnouncements($school->announcements()->getQuery())->findOrFail($announcementId);

        return view('landing.detail', [
            'school' => $school, 'title' => $announcement->title,
            'category' => 'Pengumuman', 'body' => $announcement->body,
            'date' => $announcement->published_at, 'activity' => null, 'journal' => null, 'announcement' => $announcement->load('creator:id,name'),
        ]);
    }

    public function activity(School $school, int $activityId): View
    {
        $activity = $this->findPublicActivity($school, $activityId);

        return view('landing.detail', [
            'school' => $school, 'title' => $activity->title,
            'category' => 'Kegiatan', 'body' => $activity->description,
            'date' => $activity->start_at, 'activity' => $activity, 'journal' => null,
            'results' => $this->results($activity),
            ...$this->activityHierarchy($activity),
        ]);
    }

    public function documentation(School $school, int $activityId): View
    {
        $activity = $this->findPublicActivity($school, $activityId);
        $journal = $activity->journal()->withoutGlobalScope('school')
            ->where('school_id', $school->id)->where('status', 'published')
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->with(['attachments' => fn ($query) => $query->withoutGlobalScope('school')->where('school_id', $school->id)])
            ->first();

        return view('landing.detail', [
            'school' => $school, 'title' => $activity->title,
            'category' => 'Dokumentasi kegiatan', 'body' => $activity->description,
            'date' => $activity->start_at, 'activity' => $activity, 'journal' => $journal,
        ]);
    }

    private function findPublicActivity(School $school, int $activityId): Activity
    {
        abort_unless($school->is_active, 404);

        return $this->publicActivities($school->activities()->getQuery())
            ->with(['scoutLevels', 'coaches' => fn ($query) => $query->withoutGlobalScope('school')->where('coaches.school_id', $school->id)])
            ->findOrFail($activityId);
    }

    public function globalAnnouncement(int $announcementId): View
    {
        $announcement = $this->publicAnnouncements(Announcement::query()->whereNull('school_id'))->with('creator:id,name')->findOrFail($announcementId);

        return view('landing.detail', ['school' => null, 'title' => $announcement->title, 'category' => 'Pengumuman',
            'body' => $announcement->body, 'date' => $announcement->published_at, 'announcement' => $announcement, 'activity' => null, 'journal' => null]);
    }

    public function globalActivity(int $activityId): View
    {
        $activity = $this->publicActivities(Activity::query()->whereNull('school_id'))->with('scoutLevels', 'coaches')->findOrFail($activityId);

        return view('landing.detail', ['school' => null, 'title' => $activity->title, 'category' => 'Kegiatan',
            'body' => $activity->description, 'date' => $activity->start_at, 'activity' => $activity, 'journal' => null,
            'results' => $this->results($activity), ...$this->activityHierarchy($activity)]);
    }

    public function schoolResults(School $school, int $activityId, int $assessmentId): View
    {
        return $this->resultsView($this->findPublicActivity($school, $activityId), $assessmentId, $school);
    }

    public function globalResults(int $activityId, int $assessmentId): View
    {
        $activity = $this->publicActivities(Activity::query()->whereNull('school_id'))->findOrFail($activityId);

        return $this->resultsView($activity, $assessmentId);
    }

    private function resultsView(Activity $activity, int $assessmentId, ?School $school = null): View
    {
        $service = app(PublicAssessmentService::class);
        $assessment = $service->publishedFor($activity)->firstWhere('id', $assessmentId);
        abort_unless($assessment, 404);
        $rankings = $service->rankings($assessment);
        $resultJudges = $assessment->is_special ? app(ActivityJudgeService::class)->resultJudges($assessment) : collect();

        return view('landing.results', compact('activity', 'assessment', 'rankings', 'school', 'resultJudges'));
    }

    /** @return Collection<int, array{assessment: ActivityAssessment, rankings: Collection}> */
    private function results(Activity $activity): Collection
    {
        $service = app(PublicAssessmentService::class);

        return $service->publishedFor($activity)->map(fn ($assessment): array => [
            'assessment' => $assessment, 'rankings' => $service->rankings($assessment)->whereNotNull('score')->take(3),
        ]);
    }

    private function publicActivities(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school')->where('is_public', true)
            ->where('approval_status', 'approved')
            ->whereIn('status', ['published', 'ongoing', 'completed'])
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** @return array{parentActivity: ?Activity, subActivities: Collection} */
    private function activityHierarchy(Activity $activity): array
    {
        $query = $this->publicActivities(Activity::query()->where('school_id', $activity->school_id));

        return [
            'parentActivity' => $activity->parent_activity_id ? (clone $query)->find($activity->parent_activity_id) : null,
            'subActivities' => (clone $query)->where('parent_activity_id', $activity->id)->orderBy('start_at')->orderBy('title')->get(),
        ];
    }

    private function publicAnnouncements(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school')->where('status', 'published')->where('is_public', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
