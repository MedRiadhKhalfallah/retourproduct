<?php

namespace App\Http\Controllers;

use App\Http\Requests\InspectionRequest;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class InspectionController extends Controller
{
    public function update(InspectionRequest $request, Item $item): RedirectResponse
    {
        $data = $request->validated();
        $data['cleaned'] = $request->boolean('cleaned');
        $data['is_functional'] = $request->filled('is_functional') ? $request->boolean('is_functional') : null;

        $checklist = $item->checklist()->updateOrCreate([], $data + ['inspected_at' => now()]);
        $changes = array_diff_key($checklist->getChanges(), ['updated_at' => true, 'inspected_at' => true]);

        if ($checklist->wasRecentlyCreated || $changes !== [] || $checklist->wasChanged('inspected_at')) {
            if ($checklist->wasRecentlyCreated) {
                $changes = array_diff_key($data, ['inspected_at' => true]);
            }

            $labels = [
                'package_condition' => 'Emballage',
                'is_functional' => 'Fonctionnel',
                'missing_parts_details' => 'Éléments manquants',
                'cleaned' => 'Nettoyage effectué',
            ];
            $previous = $checklist->getPrevious();
            $details = [];

            foreach ($changes as $attribute => $newValue) {
                $details[] = ($labels[$attribute] ?? $attribute).': '
                    .$this->formatValue($attribute, $previous[$attribute] ?? null)
                    .' → '.$this->formatValue($attribute, $newValue);
            }

            $item->histories()->create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'description' => 'Checklist d’inspection enregistrée'
                    .($details === [] ? '.' : ' — '.implode('; ', $details).'.'),
            ]);
        }

        return back()->with('ok', 'Checklist enregistrée.');
    }

    private function formatValue(string $attribute, mixed $value): string
    {
        if ($value === null || $value === '') {
            return $attribute === 'is_functional' ? 'Non testé' : 'non défini';
        }

        if (in_array($attribute, ['cleaned', 'is_functional'], true)) {
            return (bool) $value ? 'Oui' : 'Non';
        }

        if ($attribute === 'package_condition') {
            return match ($value) {
                'intact' => 'Intact',
                'damaged' => 'Abîmé',
                'missing' => 'Absent',
                default => (string) $value,
            };
        }

        return (string) $value;
    }
}
