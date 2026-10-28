<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLoanDocument extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'loan_id', 'document_type', 'version', 'document_number', 'storage_path',
        'sha256', 'status', 'issued_by', 'issued_at', 'correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(InventoryLoan::class, 'loan_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function prints(): HasMany
    {
        return $this->hasMany(InventoryLoanDocumentPrint::class, 'document_id');
    }
}
