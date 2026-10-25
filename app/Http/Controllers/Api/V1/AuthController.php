<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginApiRequest;
use App\Http\Requests\Api\V1\RegisterApiRequest;
use App\Http\Resources\Api\V1\SchoolResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\School;
use App\Models\User;
use App\Services\PendingAccountRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterApiRequest $request, PendingAccountRegistration $registration): JsonResponse
    {
        $user = $registration->register($request->validated());

        return response()->json([
            'message' => 'Pendaftaran berhasil dikirim dan menunggu persetujuan admin sekolah.',
            'data' => ['user' => new UserResource($user)],
        ], 202);
    }

    public function login(LoginApiRequest $request): JsonResponse
    {
        $user = User::query()->where('email', strtolower($request->string('email')->trim()->toString()))->first();
        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return response()->json(['message' => 'Email atau kata sandi tidak sesuai.'], 401);
        }
        if (! $user->is_active) {
            return response()->json(['message' => 'Akun belum aktif atau belum disetujui.'], 403);
        }
        if ($user->two_factor_confirmed_at) {
            return response()->json(['message' => 'Login API untuk akun dengan autentikasi dua faktor belum didukung.'], 403);
        }

        $schools = $user->isSystemAdmin()
            ? School::query()->where('is_active', true)->orderBy('name')->get()
            : $user->schoolMemberships()->where('is_active', true)->whereNull('left_at')->with('school')
                ->get()->pluck('school')->filter(fn ($school) => $school?->is_active)->values();

        $token = $user->createToken($request->string('device_name')->toString(), ['*'], now()->addDays(30));

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at,
                'user' => new UserResource($user->load('schoolMemberships.school')),
                'schools' => SchoolResource::collection($schools),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $schools = $user->isSystemAdmin()
            ? School::query()->where('is_active', true)->orderBy('name')->get()
            : $user->schoolMemberships()->where('is_active', true)->whereNull('left_at')->with('school')
                ->get()->pluck('school')->filter(fn ($school) => $school?->is_active)->values();

        return response()->json(['data' => [
            'user' => new UserResource($user->load('schoolMemberships.school')),
            'schools' => SchoolResource::collection($schools),
        ]]);
    }
}
