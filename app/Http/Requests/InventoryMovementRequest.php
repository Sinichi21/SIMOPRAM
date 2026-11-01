<?php

namespace App\Http\Requests;

use App\Support\SchoolContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.stock.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();
        $exists = fn (string $table) => Rule::exists($table, 'id')->where('school_id', $schoolId)->where('is_active', true)->whereNull('deleted_at');

        return [
            'item_id' => ['required', 'integer', $exists('inventory_items')->where('tracking_type', 'quantity')],
            'owner_id' => ['required', 'integer', $exists('inventory_owners')],
            'location_id' => ['required', 'integer', $exists('inventory_locations')],
            'movement_type' => ['required', Rule::in(['receipt', 'issue', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['required_if:movement_type,adjustment', 'nullable', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
