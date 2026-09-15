<?php

namespace App\Http\Controllers;

use App\Models\ActivityRegistration;
use App\Services\ActivityRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ActivityParticipantPortalController extends Controller
{
    public function open(string $token): Response
    {
        $registration = $this->tokenRegistration($token);

        return response()->view('landing.access-link', compact('registration', 'token'))->withHeaders($this->headers());
    }

    public function enter(Request $request, string $token): Response
    {
        $registration = $this->tokenRegistration($token);
        $request->session()->regenerate();
        $request->session()->put('activity_participant', ['id' => $registration->id, 'version' => $registration->access_version]);

        return redirect()->route('activity-access.portal', $registration->id)->withHeaders($this->headers());
    }

    private function tokenRegistration(string $token): ActivityRegistration
    {
        $registration = ActivityRegistration::where('token_hash', hash('sha256', $token))->firstOrFail();
        app(ActivityRegistrationService::class)->assertAccessible($registration);

        return $registration;
    }

    private function participant(Request $request, int $registrationId): ActivityRegistration
    {
        $registration = ActivityRegistration::findOrFail($registrationId);
        $session = $request->session()->get('activity_participant', []);
        $ownAccount = $request->user()?->is_active && $registration->user_id && $registration->user_id === $request->user()->id;
        abort_unless($ownAccount || (($session['id'] ?? null) === $registration->id && ($session['version'] ?? null) === $registration->access_version), 403);
        app(ActivityRegistrationService::class)->assertAccessible($registration);

        return $registration;
    }

    public function portal(Request $request, int $registrationId): Response
    {
        $registration = $this->participant($request, $registrationId);
        $registration->load('entry.members');

        return response()->view('landing.participant-portal', compact('registration'))->withHeaders($this->headers());
    }

    public function checkIn(Request $request, int $registrationId): Response
    {
        DB::transaction(function () use ($request, $registrationId): void {
            ActivityRegistration::whereKey($registrationId)->lockForUpdate()->firstOrFail();
            $registration = $this->participant($request, $registrationId);
            abort_unless($registration->entry->validation_status === 'validated', 403, 'Pendaftaran belum divalidasi pengelola.');
            abort_unless(in_array($registration->attendance_status, [null, 'present'], true), 403, 'Absensi telah dicatat pengelola. Hubungi pengelola untuk koreksi.');
            if (! $registration->checked_in_at) {
                $registration->forceFill(['checked_in_at' => now(), 'attendance_status' => 'present', 'attendance_marked_by' => null])->save();
            }
        });

        return redirect()->route('activity-access.portal', $registrationId)->with('status', 'Kehadiran berhasil dicatat.')->withHeaders($this->headers());
    }

    public function leave(Request $request): Response
    {
        $request->session()->forget('activity_participant');
        $request->session()->regenerate();

        return redirect()->route('home')->withHeaders($this->headers());
    }

    public function mine(Request $request): Response
    {
        $registrations = ActivityRegistration::where('user_id', $request->user()->id)->where('status', 'active')->with('activity', 'entry')->latest('id')->get();

        return response()->view('landing.my-activities', compact('registrations'))->withHeaders($this->headers());
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer', 'X-Robots-Tag' => 'noindex, nofollow'];
    }
}
