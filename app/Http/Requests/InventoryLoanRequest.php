<?php

namespace App\Http\Requests;

use App\Support\SchoolContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.loans.request') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();

        return [
            'borrower' => ['required', 'array:type,name,organization,contact,user_id,scout_group_id'],
            'borrower.type' => ['required', Rule::in(['manual_external', 'internal', 'registered_gudep'])],
            'borrower.name' => ['required_if:borrower.type,manual_external', 'nullable', 'string', 'max:180'],
            'borrower.organization' => ['nullable', 'string', 'max:180'],
            'borrower.contact' => ['required', 'string', 'max:100'],
            'borrower.user_id' => ['required_if:borrower.type,internal', 'nullable', 'integer'],
            'borrower.scout_group_id' => ['required_if:borrower.type,registered_gudep', 'nullable', 'integer'],
            'purpose' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*' => ['array:item_id,asset_id,quantity'],
            'items.*.item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('school_id', $schoolId)->where('is_active', true)->whereNull('deleted_at')],
            'items.*.asset_id' => ['nullable', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:99999999999', 'decimal:0,3'],
        ];
    }
}
