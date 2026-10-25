<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StudentIndexRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Student;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    public function index(StudentIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = Student::query()
            ->select([
                'id',
                'nis',
                'nisn',
                'name',
                'gender',
                'birth_date',
                'joined_at',
                'status',
            ]);

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nis', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%');
            });
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($gender = $filters['gender'] ?? null) {
            $query->where('gender', $gender);
        }

        return StudentResource::collection(
            $query
                ->orderBy('name')
                ->paginate($filters['per_page'] ?? 30)
                ->withQueryString()
        );
    }
}
