<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CoachIndexRequest;
use App\Http\Requests\Api\V1\CoachSaveRequest;
use App\Http\Resources\Api\V1\CoachResource;
use App\Models\Coach;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CoachController extends Controller
{
    public function index(CoachIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $coaches = Coach::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $search = '%'.trim($search).'%';
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('nip', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('position', 'like', $search);
                });
            })
            ->when(isset($filters['is_active']), fn (Builder $query) => $query->where('is_active', $filters['is_active']));

        return CoachResource::collection($coaches->orderBy('name')->paginate($request->integer('per_page', 20)));
    }

    public function show(int $coachId): CoachResource
    {
        return new CoachResource(Coach::query()->findOrFail($coachId));
    }

    public function store(CoachSaveRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data = $this->normalize($data);
        $coach = Coach::query()->create($data);

        return (new CoachResource($coach))->response()->setStatusCode(201);
    }

    public function update(CoachSaveRequest $request, int $coachId): CoachResource
    {
        $coach = Coach::query()->findOrFail($coachId);
        $coach->update($this->normalize($request->validated()));

        return new CoachResource($coach->refresh());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach (['nip', 'name', 'phone', 'position', 'certificate_number'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = filled($data[$field]) ? trim($data[$field]) : null;
            }
        }

        return $data;
    }
}
