<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSchoolRegistrationRequest;
use App\Models\SchoolRegistrationRequest;
use Illuminate\Http\RedirectResponse;

class SchoolRegistrationController extends Controller
{
    public function store(StoreSchoolRegistrationRequest $request): RedirectResponse
    {
        SchoolRegistrationRequest::query()->create($request->validated());

        return back()->with('school-registration-success', 'Permohonan sekolah berhasil dikirim. Tim SIMPRAM akan menghubungi Anda.');
    }
}
