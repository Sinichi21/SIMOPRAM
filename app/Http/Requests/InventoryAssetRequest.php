<?php

namespace App\Http\Requests;

use App\Support\SchoolContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();
        $exists = fn (string $table) => Rule::exists($table, 'id')->where('school_id', $schoolId)->where('is_active', true)->whereNull('deleted_at');
        if (! $this->isMethod('POST')) {
            return [
                'location_id' => ['required', 'integer', $exists('inventory_locations')],
                'condition' => ['required', Rule::in(['good', 'damaged', 'lost'])],
                'reason' => ['required', 'string', 'min:10', 'max:2000'],
            ];
        }

        return [
            'item_id' => ['required', 'integer', $exists('inventory_items')->where('tracking_type', 'individual')],
            'owner_id' => ['required', 'integer', $exists('inventory_owners')],
            'location_id' => ['required', 'integer', $exists('inventory_locations')],
            'code_prefix' => ['required', 'string', 'max:65', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:500'],
            'last_number' => ['sometimes', 'integer', 'min:0', 'max:999999999'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
