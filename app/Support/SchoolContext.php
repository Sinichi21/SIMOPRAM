<?php

namespace App\Support;

use App\Models\School;

class SchoolContext
{
    protected ?School $school = null;

    public function set(School $school): void
    {
        $this->school = $school;
    }

    public function school(): ?School
    {
        return $this->school;
    }

    public function id(): ?int
    {
        return $this->school?->id;
    }

    public function hasSchool(): bool
    {
        return $this->school !== null;
    }

    public function clear(): void
    {
        $this->school = null;
    }
}