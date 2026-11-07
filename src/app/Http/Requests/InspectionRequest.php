<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'package_condition' => ['nullable', 'in:intact,damaged,missing'],
            'is_functional' => ['nullable', 'boolean'],
            'missing_parts_details' => ['nullable', 'string'],
            'cleaned' => ['nullable', 'boolean'],
        ];
    }
}
