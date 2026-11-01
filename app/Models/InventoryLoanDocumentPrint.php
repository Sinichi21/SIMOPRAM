<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLoanDocumentPrint extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'document_id', 'printed_by', 'printed_at', 'purpose', 'reason',
    ];

    protected function casts(): array
    {
        return ['printed_at' => 'datetime'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryLoanDocument::class, 'document_id');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
