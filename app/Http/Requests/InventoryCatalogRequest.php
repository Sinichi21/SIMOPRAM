<?php

namespace App\Http\Requests;

use App\Support\SchoolContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app(SchoolContext::class)->id();
        $kind = $this->route('catalog');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $exists = fn (string $table) => Rule::exists($table, 'id')->where('school_id', $schoolId)->whereNull('deleted_at');
        $unique = fn (string $table, string $column) => Rule::unique($table, $column)->where('school_id', $schoolId)->ignore($this->route('id'));
        $shared = ['description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['sometimes', 'boolean']];

        return match ($kind) {
            'categories' => array_merge($shared, ['name' => [$required, 'string', 'max:100', $unique('inventory_categories', 'name')]]),
            'owners' => [
                'display_name' => [$required, 'string', 'max:150'],
                'owner_type' => [$required, Rule::in(['school', 'gudep'])],
                'scout_group_id' => ['nullable', 'integer', $exists('scout_groups')],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'locations' => array_merge($shared, [
                'name' => [$required, 'string', 'max:120', $unique('inventory_locations', 'name')],
                'location_type' => [$required, Rule::in(['storage', 'room', 'vehicle', 'other'])],
                'scout_group_id' => ['nullable', 'integer', $exists('scout_groups')],
            ]),
            'items' => array_merge($shared, [
                'category_id' => [$required, 'integer', $exists('inventory_categories')],
                'item_code' => [$required, 'string', 'max:60', $unique('inventory_items', 'item_code')],
                'name' => [$required, 'string', 'max:180'],
                'tracking_type' => [$required, Rule::in(['quantity', 'individual'])],
                'unit' => [$required, 'string', 'max:30'],
                'minimum_stock' => ['sometimes', 'numeric', 'min:0', 'max:99999999999', 'decimal:0,3'],
                'description' => ['nullable', 'string', 'max:4000'],
            ]),
            default => [],
        };
    }
}
