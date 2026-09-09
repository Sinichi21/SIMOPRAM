<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\LandingPageSetting;
use App\Models\School;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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

        return view('landing.index', compact('schools', 'search', 'content', 'heroImage'));
    }

    public function school(School $school): View
    {
        abort_unless($school->is_active, 404);

        $school->load([
            'announcements' => fn ($query) => $this->publicAnnouncements($query->getQuery())
                ->latest('published_at')->limit(3),
            'activities' => fn ($query) => $this->publicActivities($query->getQuery())
                ->latest('start_at')->limit(6),
            'coaches' => fn ($query) => $query->withoutGlobalScope('school')->with('user:id,email')->where('is_active', true)->orderBy('name')->limit(3),
        ]);

        return view('landing.school', compact('school'));
    }

    public function announcement(School $school, int $announcementId): View
    {
        abort_unless($school->is_active, 404);
        $announcement = $this->publicAnnouncements($school->announcements()->getQuery())->findOrFail($announcementId);

        return view('landing.detail', [
            'school' => $school, 'title' => $announcement->title,
            'category' => 'Pengumuman', 'body' => $announcement->body,
            'date' => $announcement->published_at, 'activity' => null, 'journal' => null,
        ]);
    }

    public function activity(School $school, int $activityId): View
    {
        $activity = $this->findPublicActivity($school, $activityId);

        return view('landing.detail', [
            'school' => $school, 'title' => $activity->title,
            'category' => 'Kegiatan', 'body' => $activity->description,
            'date' => $activity->start_at, 'activity' => $activity, 'journal' => null,
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

        return $this->publicActivities($school->activities()->getQuery())->findOrFail($activityId);
    }

    private function publicActivities(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school')->where('is_public', true)
            ->whereIn('status', ['published', 'ongoing', 'completed'])
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    private function publicAnnouncements(Builder $query): Builder
    {
        return $query->withoutGlobalScope('school')->where('status', 'published')->where('is_public', true)
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
