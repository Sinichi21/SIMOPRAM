<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Support\SchoolContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ComplaintController extends Controller
{
    public function publicForm(): View
    {
        return view('complaints.public', ['trackedComplaint' => null]);
    }

    public function publicStore(StoreComplaintRequest $request): RedirectResponse
    {
        $complaint = $this->createComplaint($request, false);

        return to_route('complaints.public')->with('complaint-reference', $complaint->reference);
    }

    public function track(Request $request): View
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:36'],
            'tracking_email' => ['required', 'email', 'max:150'],
        ]);
        $complaint = Complaint::query()
            ->whereNull('school_id')
            ->where('reference', Str::upper(trim($data['reference'])))
            ->where('email', $data['tracking_email'])
            ->first();

        if (! $complaint) {
            throw ValidationException::withMessages(['reference' => 'Nomor pengaduan atau email tidak sesuai.']);
        }

        return view('complaints.public', ['trackedComplaint' => $complaint]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Complaint::STATUSES))],
            'q' => ['nullable', 'string', 'max:180'],
        ]);
        $complaints = $this->visibleComplaints($request)
            ->with('school')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $query) use ($search): void {
                $query->where('subject', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%');
            }))
            ->latest()->paginate(15)->withQueryString();

        return view('complaints.index', [
            'complaints' => $complaints,
            'canManage' => $this->canManage($request),
            'school' => app(SchoolContext::class)->school(),
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        abort_unless(app(SchoolContext::class)->hasSchool(), 409, 'Pilih sekolah aktif terlebih dahulu.');
        $complaint = $this->createComplaint($request, true);

        return to_route('complaints.show', $complaint->id)->with('success', 'Pengaduan berhasil dikirim ke pengelola sekolah.');
    }

    public function show(Request $request, int $complaint): View
    {
        return view('complaints.show', [
            'complaint' => $this->visibleComplaints($request)->with('school')->findOrFail($complaint),
            'canManage' => $this->canManage($request),
        ]);
    }

    public function update(Request $request, int $complaint): RedirectResponse
    {
        $record = $this->visibleComplaints($request)->findOrFail($complaint);
        abort_unless($this->canManage($request), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Complaint::STATUSES))],
            'response' => ['required', 'string', 'min:10', 'max:10000'],
        ]);
        $record->update([...$data, 'responded_by' => $request->user()->id]);

        return to_route('complaints.show', $record->id)->with('success', 'Status dan tanggapan berhasil disimpan.');
    }

    public function attachment(Request $request, int $complaint): StreamedResponse
    {
        $record = $this->visibleComplaints($request)->findOrFail($complaint);
        abort_unless($record->attachment_path && Storage::disk('local')->exists($record->attachment_path), 404);

        return Storage::disk('local')->download($record->attachment_path);
    }

    private function canManage(Request $request): bool
    {
        return $request->user()->isSuperAdmin()
            || (app(SchoolContext::class)->hasSchool()
                && ($request->user()->isScoutAdmin() || $request->user()->can('schools.update')));
    }

    private function visibleComplaints(Request $request): Builder
    {
        $query = Complaint::query();
        if ($request->user()->isSuperAdmin()) {
            return $query;
        }

        $schoolId = app(SchoolContext::class)->id();
        if (! $schoolId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('school_id', $schoolId);
        if (! $this->canManage($request)) {
            $query->where('user_id', $request->user()->id);
        }

        return $query;
    }

    private function createComplaint(StoreComplaintRequest $request, bool $internal): Complaint
    {
        $data = $request->safe()->only(['name', 'email', 'category', 'subject', 'body']);
        $path = $request->file('attachment')?->store('complaints', 'local');
        if ($request->hasFile('attachment') && ! $path) {
            throw ValidationException::withMessages(['attachment' => 'Lampiran gagal disimpan. Silakan coba lagi.']);
        }
        try {
            return Complaint::query()->create([
                ...$data,
                'reference' => 'ADU-'.Str::upper(Str::random(24)),
                'school_id' => $internal ? app(SchoolContext::class)->id() : null,
                'user_id' => $internal ? $request->user()->id : null,
                'name' => $internal ? $request->user()->name : $data['name'],
                'email' => $internal ? $request->user()->email : $data['email'],
                'attachment_path' => $path,
                'status' => 'new',
            ]);
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
