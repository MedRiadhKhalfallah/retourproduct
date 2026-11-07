<?php

namespace App\Http\Requests;

use App\Enums\ItemStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'original_sku' => ['nullable', 'string', 'max:100'],
            'internal_reference' => ['nullable', 'string', 'max:100', Rule::unique('items', 'internal_reference')->ignore($this->route('item'))],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('email', User::ITEM_ASSIGNMENT_EMAILS),
            ],
            'condition' => ['required', 'in:new,like_new,used,damaged,unknown'],
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
            'estimated_msrp' => ['nullable', 'numeric', 'min:0'],
            'listing_date' => ['nullable', 'date'],
            'marketplace_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
            'supplier_product_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
            'asking_price' => ['nullable', 'numeric', 'min:0'],
            'bottom_price' => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('asking_price'), 'lte:asking_price')],
            'final_sale_price' => [
                Rule::requiredIf(fn () => $this->input('status') === ItemStatus::Sold->value),
                'nullable', 'numeric', 'min:0',
            ],
            'sale_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
            'inspection_notes' => ['nullable', 'string'],
            'issues_description' => ['nullable', 'string'],
        ];
    }

    /** Ajoute https:// avant validation si l'utilisateur l'a omis. */
    protected function prepareForValidation(): void
    {
        foreach (['marketplace_url', 'supplier_product_url'] as $field) {
            $url = trim((string) $this->input($field));
            if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
                $this->merge([$field => 'https://'.$url]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'bottom_price.lte' => 'Le prix plancher ne peut pas dépasser le prix affiché.',
            'final_sale_price.required' => 'Le prix de vente final est requis pour un article vendu.',
        ];
    }
}
