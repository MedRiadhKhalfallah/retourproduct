@php $input = 'w-full rounded-xl border border-slate-300 px-3 py-3 md:py-2 text-base md:text-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none'; @endphp
<div class="bg-white rounded-2xl shadow-sm p-4 md:p-6 space-y-4">
    <div class="grid gap-4 md:grid-cols-2">
        <label class="block text-sm font-medium">Référence / SKU<input name="reference" class="{{ $input }}" value="{{ old('reference', $pallet->reference) }}" required></label>
        <label class="block text-sm font-medium">Fournisseur<input name="supplier_name" class="{{ $input }}" value="{{ old('supplier_name', $pallet->supplier_name) }}" required></label>
        <label class="block text-sm font-medium">Prix d'achat total (€)<input type="number" inputmode="decimal" step="0.01" min="0" name="total_cost" class="{{ $input }}" value="{{ old('total_cost', $pallet->total_cost) }}" required></label>
        <label class="block text-sm font-medium">Date d'achat<input type="date" name="purchase_date" class="{{ $input }}" value="{{ old('purchase_date', optional($pallet->purchase_date)->format('Y-m-d')) }}" required></label>
        <label class="block text-sm font-medium">Statut
            <select name="status" class="{{ $input }}">
                @foreach (['open' => 'Ouverte', 'closed' => 'Clôturée'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('status', $pallet->status) === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-medium">Pièces jointes (photos, factures, reçus)
            <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf" class="{{ $input }}">
            <span class="text-xs font-normal text-slate-500">PDF, PNG, JPG — 10 Mo max par fichier. Les fichiers existants se gèrent sur la page de la palette.</span>
        </label>
    </div>
    <label class="block text-sm font-medium">Notes<textarea name="notes" rows="3" class="{{ $input }}">{{ old('notes', $pallet->notes) }}</textarea></label>
    <button class="w-full md:w-auto px-6 py-4 md:py-2 bg-slate-900 text-white rounded-xl text-base md:text-sm font-medium">Enregistrer</button>
</div>
