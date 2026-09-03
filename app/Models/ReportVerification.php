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
    | Semua operasi administratif wajib melakukan scope school_id secara
    | eksplisit.
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'school_id',
        'semester_closure_id',
        'code',
        'document_type',
        'snapshot_checksum',

        'file_disk',
        'file_path',
        'file_name',
        'file_sha256',
        'file_size',
        'archived_at',

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
            'file_size' =>
                'integer',

            'archived_at' =>
                'datetime',

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


    /*
    |--------------------------------------------------------------------------
    | Closure Public-Safe
    |--------------------------------------------------------------------------
    |
    | SemesterClosure menggunakan global tenant scope. Halaman verifikasi
    | publik tidak memiliki SchoolContext, sehingga relation ini secara sengaja
    | melepas global scope. Record closure tetap tidak dapat ditebak melalui
    | halaman publik karena entry point-nya adalah verification code acak.
    |--------------------------------------------------------------------------
    */

    public function closure(): BelongsTo
    {
        return $this
            ->belongsTo(
                SemesterClosure::class,
                'semester_closure_id'
            )
            ->withoutGlobalScopes();
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


    public function hasArchivedPdf(): bool
    {
        return filled(
            $this->file_path
        )
            &&
            filled(
                $this->file_sha256
            );
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
