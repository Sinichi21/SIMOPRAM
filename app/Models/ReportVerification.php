<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVerification extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Catatan Tenant
    |--------------------------------------------------------------------------
    |
    | Model ini sengaja TIDAK menggunakan BelongsToSchool karena halaman
    | /verify/report/{code} harus dapat dibuka tanpa SchoolContext/login.
    |
    | Seluruh pembuatan record dilakukan melalui ReportVerificationService,
    | sedangkan akses publik hanya menggunakan kode acak 48 karakter.
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'school_id',
        'semester_closure_id',
        'code',
        'document_type',
        'snapshot_checksum',
        'issued_by',
        'issued_at',
        'verification_count',
        'last_verified_at',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
    ];


    protected function casts(): array
    {
        return [
            'issued_at' =>
                'datetime',

            'verification_count' =>
                'integer',

            'last_verified_at' =>
                'datetime',

            'revoked_at' =>
                'datetime',
        ];
    }


    public function school(): BelongsTo
    {
        return $this->belongsTo(
            School::class
        );
    }


    public function closure(): BelongsTo
    {
        return $this->belongsTo(
            SemesterClosure::class,
            'semester_closure_id'
        );
    }


    public function issuer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'issued_by'
        );
    }


    public function revoker(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by'
        );
    }


    public function isRevoked(): bool
    {
        return $this->revoked_at
            !== null;
    }


    public function publicStatus(): string
    {
        if (
            $this->isRevoked()
        ) {
            return 'revoked';
        }

        if (
            $this->closure
            &&
            $this->closure->isReopened()
        ) {
            return 'superseded';
        }

        return 'valid';
    }
}
