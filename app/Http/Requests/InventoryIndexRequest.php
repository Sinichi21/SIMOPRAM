<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.view') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'search' => ['sometimes', 'nullable', 'string', 'max:180'],
            'code' => ['sometimes', 'string', 'max:2048'],
            'status' => ['sometimes', 'string', 'max:40'],
            'condition' => ['sometimes', Rule::in(['good', 'damaged', 'lost', 'missing'])],
            'is_active' => ['sometimes', 'boolean'],
            'tracking_type' => ['sometimes', Rule::in(['quantity', 'individual'])],
            'item_id' => ['sometimes', 'integer', 'min:1'],
            'owner_id' => ['sometimes', 'integer', 'min:1'],
            'location_id' => ['sometimes', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
