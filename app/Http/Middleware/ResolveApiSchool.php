<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Support\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiSchool
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $schoolId = $request->header('X-School-Id');
        if (! $schoolId) {
            return response()->json(['message' => 'Header X-School-Id wajib diisi.'], 422);
        }

        $school = School::query()->whereKey($schoolId)->where('is_active', true)->first();
        if (! $school) {
            return response()->json(['message' => 'Sekolah tidak tersedia.'], 404);
        }

        if (! $user->isSystemAdmin() && ! $user->schoolMemberships()
            ->where('school_id', $school->id)->where('is_active', true)->whereNull('left_at')->exists()) {
            return response()->json(['message' => 'Anda tidak memiliki keanggotaan aktif di sekolah ini.'], 403);
        }

        $context = app(SchoolContext::class);
        $context->set($school);
        setPermissionsTeamId($school->id);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        $request->attributes->set('api_school', $school);

        try {
            return $next($request);
        } finally {
            $context->clear();
            setPermissionsTeamId(null);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
    }
}
