<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolRegistrationRequest extends Model
{
    protected $fillable = [
        'school_name',
        'npsn',
        'level',
        'city',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
    ];
}
