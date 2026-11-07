<?php

namespace App\Observers;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ItemObserver
{
    public function created(Item $item): void
    {
        $details = ['Article « '.$item->name.' » créé'];
        $details[] = 'Statut : '.$this->formatValue('status', $item->status);
        $details[] = 'Responsable : '.$this->formatValue('assigned_to', $item->assigned_to);

        if ($item->asking_price !== null) {
            $details[] = 'Prix affiché : '.$this->formatValue('asking_price', $item->asking_price);
        }

        $item->histories()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'description' => implode('. ', $details).'.',
        ]);
    }

    public function updated(Item $item): void
    {
        $changes = array_diff_key($item->getChanges(), ['updated_at' => true]);
        if ($changes === []) {
            return;
        }

        $previous = $item->getPrevious();
        $descriptions = [];

        foreach ($changes as $attribute => $newValue) {
            $oldValue = $previous[$attribute] ?? null;
            $label = $this->label($attribute);

            if ($attribute === 'status') {
                $descriptions[] = $label.' changé de « '.$this->formatValue($attribute, $oldValue)
                    .' » à « '.$this->formatValue($attribute, $newValue).' »';
            } elseif (in_array($attribute, ['asking_price', 'bottom_price', 'final_sale_price'], true)) {
                $descriptions[] = $label.' : '.$this->formatValue($attribute, $oldValue)
                    .' → '.$this->formatValue($attribute, $newValue);
            } elseif ($attribute === 'image_path') {
                $descriptions[] = $newValue ? 'Photo ajoutée ou remplacée' : 'Photo supprimée';
            } else {
                $descriptions[] = $label.' : '.$this->formatValue($attribute, $oldValue)
                    .' → '.$this->formatValue($attribute, $newValue);
            }
        }

        $item->histories()->create([
            'user_id' => Auth::id(),
            'action' => array_key_exists('status', $changes) ? 'status_changed' : 'updated',
            'description' => implode('; ', $descriptions).'.',
        ]);
    }

    private function label(string $attribute): string
    {
        return match ($attribute) {
            'name' => 'Nom',
            'original_sku' => 'SKU d’origine',
            'internal_reference' => 'Référence interne',
            'assigned_to' => 'Responsable',
            'condition' => 'État',
            'status' => 'Statut',
            'purchase_cost_share' => 'Coût d’achat réparti',
            'estimated_msrp' => 'MSRP estimé',
            'listing_date' => 'Date de mise en vente',
            'marketplace_url' => 'Lien de l’annonce',
            'supplier_product_url' => 'Lien fiche vendeur',
            'asking_price' => 'Prix affiché',
            'bottom_price' => 'Prix plancher',
            'final_sale_price' => 'Prix de vente final',
            'sale_date' => 'Date de vente',
            'image_path' => 'Photo',
            'inspection_notes' => 'Notes d’inspection',
            'issues_description' => 'Problèmes / défauts',
            default => ucfirst(str_replace('_', ' ', $attribute)),
        };
    }

    private function formatValue(string $attribute, mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'non défini';
        }

        if ($attribute === 'status') {
            $status = $value instanceof ItemStatus ? $value : ItemStatus::tryFrom((string) $value);

            return $status?->label() ?? (string) $value;
        }

        if (in_array($attribute, ['asking_price', 'bottom_price', 'final_sale_price', 'purchase_cost_share', 'estimated_msrp'], true)) {
            return number_format((float) $value, 2, '.', '').' €';
        }

        if (in_array($attribute, ['listing_date', 'sale_date'], true)) {
            return is_string($value) ? date('d/m/Y', strtotime($value)) : $value->format('d/m/Y');
        }

        if ($attribute === 'marketplace_url') {
            return 'lien défini';
        }

        if ($attribute === 'assigned_to') {
            return User::find($value)?->name ?? 'Non assigné';
        }

        if ($attribute === 'supplier_product_url') {
            return (string) $value;
        }

        if ($attribute === 'image_path') {
            return 'photo définie';
        }

        return (string) $value;
    }
}
