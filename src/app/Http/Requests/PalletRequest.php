<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('pallet')?->id;

        return [
            'reference' => ['required', 'string', 'max:100', "unique:pallets,reference,{$id}"],
            'supplier_name' => ['required', 'string', 'max:150'],
            'total_cost' => ['required', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'status' => ['nullable', 'in:open,closed'],
            'attachments' => ['nullable', 'array', 'max:20'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
