<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'category' => ['required', Rule::in(Complaint::CATEGORIES)],
            'subject' => ['required', 'string', 'min:5', 'max:180'],
            'body' => ['required', 'string', 'min:20', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'nama', 'email' => 'email', 'category' => 'kategori', 'subject' => 'judul', 'body' => 'uraian', 'attachment' => 'lampiran'];
    }
}
