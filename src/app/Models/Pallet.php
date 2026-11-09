<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Pallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'supplier_name', 'total_cost', 'purchase_date',
        'status', 'notes',
    ];

    public const ATTACHMENT_DISK = 'local';

    protected static function booted(): void
    {
        static::deleting(function (Pallet $pallet) {
            $pallet->items()
                ->select(['id', 'image_path'])
                ->get()
                ->each(fn (Item $item) => $item->deleteStoredImage($item->image_path));
            $pallet->attachments->each->delete();
            Storage::disk(self::ATTACHMENT_DISK)->deleteDirectory("pallets/{$pallet->id}");
        });
    }

    protected $casts = [
        'purchase_date' => 'date',
        'total_cost' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Calcule les lignes et les totaux du rapport financier à partir des articles chargés.
     */
    public function financialReport(): array
    {
        $items = $this->items;
        $soldItems = $items->filter(
            fn (Item $item): bool => $item->status === ItemStatus::Sold && $item->final_sale_price !== null,
        );

        return [
            'items' => $items->map(function (Item $item): array {
                $isSold = $item->status === ItemStatus::Sold;
                $hasZeroResaleValue = $item->status->hasZeroResaleValue();
                $salePrice = $item->sale_value;
                $purchasePrice = (float) $item->purchase_cost_share;

                return [
                    'item' => $item,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice === null ? null : (float) $salePrice,
                    'sale_is_estimate' => ! $isSold && ! $hasZeroResaleValue,
                    'sale_label' => $hasZeroResaleValue ? 'Valeur retenue' : ($isSold ? 'Prix final' : 'Prix cible'),
                    'profit_label' => $hasZeroResaleValue ? 'Perte calculée' : ($isSold ? 'Gain / perte' : 'Gain estimé / perte estimée'),
                    'profit' => $salePrice === null ? null : round((float) $salePrice - $purchasePrice, 2),
                ];
            }),
            'purchase_total' => (float) $this->total_cost,
            'revenue_total' => $this->revenue,
            'average_purchase_price' => $items->isEmpty()
                ? null
                : round((float) $this->total_cost / $items->count(), 2),
            'average_sale_price' => $soldItems->isEmpty()
                ? null
                : round((float) $soldItems->sum('final_sale_price') / $soldItems->count(), 2),
            'net_profit' => $this->profit,
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PalletAttachment::class);
    }

    /** Enregistre les pièces jointes en base de données. */
    public function addAttachments(array $files): void
    {
        foreach ($files as $file) {
            $path = StoredFile::PATH_PREFIX."pallets/{$this->id}/".$file->hashName();
            $mimeType = $file->getMimeType();
            $contentBase64 = base64_encode($file->getContent());

            DB::transaction(function () use ($file, $path, $mimeType, $contentBase64): void {
                StoredFile::create([
                    'path' => $path,
                    'mime_type' => $mimeType,
                    'content_base64' => $contentBase64,
                ]);

                $this->attachments()->create([
                    'disk' => StoredFile::DATABASE_DISK,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'size' => $file->getSize(),
                ]);
            });
        }
    }

    /** Revenu généré (articles vendus). */
    protected function revenue(): Attribute
    {
        return Attribute::get(fn () => (float) $this->items
            ->where('status', ItemStatus::Sold)
            ->sum('final_sale_price'));
    }

    /** Profit net = revenu - investissement total. */
    protected function profit(): Attribute
    {
        return Attribute::get(fn () => round($this->revenue - (float) $this->total_cost, 2));
    }

    /** Rentabilité en % de l'investissement. */
    protected function profitability(): Attribute
    {
        return Attribute::get(fn () => (float) $this->total_cost > 0
            ? round($this->profit / (float) $this->total_cost * 100, 1)
            : null);
    }

    /**
     * Répartit total_cost sur les articles au pro-rata du MSRP estimé
     * (répartition égale si aucun MSRP n'est renseigné).
     */
    public function allocateCosts(): void
    {
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            return;
        }

        $totalMsrp = (float) $items->sum('estimated_msrp');
        $total = (float) $this->total_cost;

        foreach ($items as $item) {
            $share = $totalMsrp > 0
                ? $total * ((float) $item->estimated_msrp / $totalMsrp)
                : $total / $items->count();
            $item->update(['purchase_cost_share' => round($share, 2)]);
        }
    }
}
