<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PalletAttachment extends Model
{
    protected $fillable = ['pallet_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    protected static function booted(): void
    {
        static::deleting(function (self $attachment): void {
            if ($attachment->disk === StoredFile::DATABASE_DISK) {
                StoredFile::query()->where('path', $attachment->path)->delete();

                return;
            }

            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function pallet(): BelongsTo
    {
        return $this->belongsTo(Pallet::class);
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/')
            || preg_match('/\.(jpe?g|png)$/i', $this->original_name) === 1;
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf' || str_ends_with(strtolower($this->original_name), '.pdf');
    }

    public function getHumanSizeAttribute(): string
    {
        return $this->size > 0 ? number_format($this->size / 1024, 0, ',', ' ').' Ko' : '';
    }
}
