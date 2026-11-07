<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Item extends Model
{
    use HasFactory;

    public const IMAGE_DISK = 'public';

    protected $fillable = [
        'pallet_id', 'assigned_to', 'name', 'original_sku', 'internal_reference', 'condition', 'status',
        'purchase_cost_share', 'estimated_msrp',
        'listing_date', 'marketplace_url', 'supplier_product_url', 'asking_price', 'bottom_price',
        'final_sale_price', 'sale_date', 'image_path',
        'inspection_notes', 'issues_description',
    ];

    protected $attributes = ['status' => 'non_verifie'];

    protected $casts = [
        'status' => ItemStatus::class,
        'listing_date' => 'date',
        'sale_date' => 'date',
        'purchase_cost_share' => 'decimal:2',
        'estimated_msrp' => 'decimal:2',
        'asking_price' => 'decimal:2',
        'bottom_price' => 'decimal:2',
        'final_sale_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Item $item) {
            if ($item->image_path) {
                Storage::disk(self::IMAGE_DISK)->delete($item->image_path);
            }
        });
    }

    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(InspectionChecklist::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ItemHistory::class)->latest()->orderByDesc('id');
    }

    /** Stocke la photo principale (remplace l'ancienne) et renvoie le chemin. */
    public function storeImage(UploadedFile $file): string
    {
        if ($this->image_path) {
            Storage::disk(self::IMAGE_DISK)->delete($this->image_path);
        }

        return $file->store('items', self::IMAGE_DISK);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path
            ? Storage::disk(self::IMAGE_DISK)->url($this->image_path)
            : null);
    }

    /** URL vide => null ; ajoute https:// si le schéma est absent. */
    protected function marketplaceUrl(): Attribute
    {
        return Attribute::set(function ($value) {
            $value = trim((string) $value);

            return $value === '' ? null : (preg_match('#^https?://#i', $value) ? $value : 'https://'.$value);
        });
    }

    /** Description d'annonce combinée : vérification + problèmes. */
    protected function listingDescription(): Attribute
    {
        return Attribute::get(function () {
            $parts = [];
            if (filled($this->inspection_notes)) {
                $parts[] = "Vérification :\n".trim($this->inspection_notes);
            }
            if (filled($this->issues_description)) {
                $parts[] = "Défauts / problèmes :\n".trim($this->issues_description);
            }

            return implode("\n\n", $parts);
        });
    }

    /** Marge brute = prix de vente réel - prix d'achat unitaire (null tant que non vendu). */
    protected function margin(): Attribute
    {
        return Attribute::get(fn () => $this->final_sale_price === null
            ? null
            : round((float) $this->final_sale_price - (float) $this->purchase_cost_share, 2));
    }

    /** Marge en % du prix d'achat unitaire. */
    protected function marginPercent(): Attribute
    {
        return Attribute::get(fn () => $this->margin === null || (float) $this->purchase_cost_share <= 0
            ? null
            : round($this->margin / (float) $this->purchase_cost_share * 100, 1));
    }

    /** Marge potentielle sur le prix affiché. */
    protected function expectedMargin(): Attribute
    {
        return Attribute::get(fn () => $this->asking_price === null
            ? null
            : round((float) $this->asking_price - (float) $this->purchase_cost_share, 2));
    }

    /** Applique une transition de workflow si autorisée. */
    public function transitionTo(ItemStatus $to): bool
    {
        if (! in_array($to, $this->status->next(), true)) {
            return false;
        }

        return $this->update(['status' => $to]);
    }
}
