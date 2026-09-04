<?php

namespace App\Http\Controllers;

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

        return view('landing.index', compact('schools', 'search'));
    }

    public function school(School $school): View
    {
        abort_unless($school->is_active, 404);

        $school->load([
            'announcements' => fn ($query) => $query->where('status', 'published')
                ->where('is_public', true)
                ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->latest('published_at')->limit(3),
            'activities' => fn ($query) => $query->where('is_public', true)
                ->whereIn('status', ['published', 'ongoing', 'completed'])
                ->latest('start_at')->limit(6),
            'coaches' => fn ($query) => $query->where('is_active', true)->orderBy('name')->limit(3),
        ]);

        return view('landing.school', compact('school'));
    }
}
