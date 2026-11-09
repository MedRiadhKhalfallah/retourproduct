@php
    $input = 'w-full rounded-xl border border-slate-300 px-3 py-3 md:py-2 text-base md:text-sm focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none';
    $label = 'block text-sm font-medium text-slate-700';
    $conditions = ['new' => 'Neuf', 'like_new' => 'Comme neuf', 'used' => 'Occasion', 'damaged' => 'Endommagé', 'unknown' => 'Inconnu'];
    $date = fn ($d) => optional($d)->format('Y-m-d');
@endphp

<div class="space-y-6">
    <section class="bg-white rounded-2xl shadow-sm p-4 md:p-6">
        <h2 class="font-semibold mb-4">📷 Photo & identification</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="{{ $label }}">Photo principale</label>
                @if ($item->image_url)
                    <img id="photo-preview" src="{{ $item->image_url }}" alt="{{ $item->name }}" class="mt-2 h-40 rounded-xl object-cover">
                    <p id="photo-placeholder" class="hidden"></p>
                @else
                    <p id="photo-placeholder" class="mt-2 flex h-40 items-center justify-center rounded-xl bg-slate-100 px-4 text-center text-sm text-slate-500">
                        {{ $item->image_path ? 'Photo enregistrée introuvable. Téléversez-la à nouveau.' : 'Aucune photo sélectionnée.' }}
                    </p>
                    <img id="photo-preview" src="" alt="{{ $item->name }}" class="mt-2 h-40 rounded-xl object-cover hidden">
                @endif
                <label class="mt-2 flex w-full md:w-auto md:inline-flex items-center justify-center gap-2 cursor-pointer rounded-xl bg-slate-900 text-white px-4 py-4 md:py-2 text-base md:text-sm active:bg-slate-700">
                    📷 Prendre / choisir une photo
                    <input id="photo-input" type="file" name="image" accept="image/*" capture="environment" class="sr-only">
                </label>
            </div>
            <label class="{{ $label }} md:col-span-2">Titre de l'article<input name="name" class="{{ $input }}" value="{{ old('name', $item->name) }}" required></label>
            <label class="{{ $label }}">SKU d'origine<input name="original_sku" class="{{ $input }}" value="{{ old('original_sku', $item->original_sku) }}"></label>
            <label class="{{ $label }}">Ma référence (stock)<input name="internal_reference" maxlength="100" placeholder="ex : STK-0042" class="{{ $input }}" value="{{ old('internal_reference', $item->internal_reference) }}"></label>
            <label class="{{ $label }}">Responsable
                <select name="assigned_to" class="{{ $input }}">
                    <option value="">Non assigné</option>
                    @foreach ($assignees as $assignee)
                        <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $item->assigned_to) === (string) $assignee->id)>{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="{{ $label }}">État
                <select name="condition" class="{{ $input }}">
                    @foreach ($conditions as $v => $l)<option value="{{ $v }}" @selected(old('condition', $item->condition) === $v)>{{ $l }}</option>@endforeach
                </select>
            </label>
            <label class="{{ $label }}">Statut
                <select name="status" class="{{ $input }}">
                    @foreach (\App\Enums\ItemStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', $item->status?->value ?? 'non_verifie') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </section>

    <section class="bg-white rounded-2xl shadow-sm p-4 md:p-6">
        <h2 class="font-semibold mb-4">💶 Prix & vente</h2>
        <div class="grid gap-4 md:grid-cols-3">
            <label class="{{ $label }}">Prix neuf / MSRP (€)<input type="number" inputmode="decimal" step="0.01" min="0" name="estimated_msrp" class="{{ $input }}" value="{{ old('estimated_msrp', $item->estimated_msrp) }}"></label>
            <label class="{{ $label }}">Prix affiché (€)<input type="number" inputmode="decimal" step="0.01" min="0" name="asking_price" class="{{ $input }}" value="{{ old('asking_price', $item->asking_price) }}"></label>
            <label class="{{ $label }}">Prix plancher (€)<input type="number" inputmode="decimal" step="0.01" min="0" name="bottom_price" class="{{ $input }}" value="{{ old('bottom_price', $item->bottom_price) }}"></label>
            <label class="{{ $label }}">Date de mise en vente<input type="date" name="listing_date" class="{{ $input }}" value="{{ old('listing_date', $date($item->listing_date)) }}"></label>
            <label class="{{ $label }} md:col-span-2">Lien de l'annonce (Marketplace, Kijiji, eBay…)<input type="url" inputmode="url" name="marketplace_url" placeholder="https://" class="{{ $input }}" value="{{ old('marketplace_url', $item->marketplace_url) }}"></label>
            <label class="{{ $label }} md:col-span-2">Lien de la fiche produit chez le vendeur principal<input type="url" inputmode="url" name="supplier_product_url" placeholder="https://" class="{{ $input }}" value="{{ old('supplier_product_url', $item->supplier_product_url) }}"></label>
            <label class="{{ $label }}">Prix de vente final (€)<input type="number" inputmode="decimal" step="0.01" min="0" name="final_sale_price" class="{{ $input }}" value="{{ old('final_sale_price', $item->final_sale_price) }}"></label>
            <label class="{{ $label }}">Date de vente<input type="date" name="sale_date" class="{{ $input }}" value="{{ old('sale_date', $date($item->sale_date)) }}"></label>
        </div>
        <p class="text-xs text-slate-500 mt-3">Prix final requis si le statut est « Vendu ». Le prix d'achat unitaire est calculé au pro-rata du MSRP.</p>
    </section>

    <section class="bg-white rounded-2xl shadow-sm p-4 md:p-6">
        <h2 class="font-semibold mb-4">🔍 Vérification</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="{{ $label }}">Description de la vérification (tests, pièces présentes)<textarea name="inspection_notes" rows="5" class="{{ $input }}">{{ old('inspection_notes', $item->inspection_notes) }}</textarea></label>
            <label class="{{ $label }}">Problèmes, défauts, rayures<textarea name="issues_description" rows="5" class="{{ $input }}">{{ old('issues_description', $item->issues_description) }}</textarea></label>
        </div>
    </section>

    <button class="w-full md:w-auto px-6 py-4 md:py-2 bg-slate-900 text-white rounded-xl text-base md:text-sm font-medium active:bg-slate-700">Enregistrer</button>
</div>

@push('scripts')
<script>
    document.getElementById('photo-input').addEventListener('change', e => {
        const f = e.target.files[0], img = document.getElementById('photo-preview');
        if (f) {
            img.src = URL.createObjectURL(f);
            img.classList.remove('hidden');
            document.getElementById('photo-placeholder').classList.add('hidden');
        }
    });
</script>
@endpush
