<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLoanEvidence extends Model
{
    use BelongsToSchool;

    protected $table = 'inventory_loan_evidence';

    protected $fillable = [
        'loan_id', 'loan_event_id', 'disk', 'path', 'mime_type',
        'size_bytes', 'sha256', 'uploaded_by',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(InventoryLoanEvent::class, 'loan_event_id');
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(InventoryLoan::class, 'loan_id');
    }
}
