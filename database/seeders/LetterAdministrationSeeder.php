<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\LetterAdministrationBootstrapService;
use Illuminate\Database\Seeder;

class LetterAdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrap = app(LetterAdministrationBootstrapService::class);
        School::query()->select('id')->each(fn (School $school) => $bootstrap->ensureForSchool($school->id));
    }
}
