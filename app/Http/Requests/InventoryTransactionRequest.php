<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = $this->routeIs('*.handover') ? 'inventory.handover.process' : 'inventory.returns.process';

        return $this->user()?->can($permission) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $handover = $this->routeIs('*.handover');

        return [
            'recording_method' => ['required', Rule::in(['online', 'offline'])],
            'occurred_at' => ['required_if:recording_method,offline', 'nullable', 'date', 'before_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:500'],
            'lines.*' => [$handover ? 'array:loan_item_id,quantity,condition' : 'array:loan_item_id,quantity_returned,quantity_missing,condition,condition_notes'],
            'lines.*.loan_item_id' => ['required', 'integer', 'distinct'],
            'lines.*.quantity' => [$handover ? 'required' : 'prohibited', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
            'lines.*.quantity_returned' => [$handover ? 'prohibited' : 'required', 'numeric', 'min:0', 'max:99999999999', 'decimal:0,3'],
            'lines.*.quantity_missing' => [$handover ? 'prohibited' : 'sometimes', 'numeric', 'min:0', 'max:99999999999', 'decimal:0,3'],
            'lines.*.condition' => ['sometimes', Rule::in($handover ? ['good'] : ['good', 'damaged'])],
            'lines.*.condition_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
