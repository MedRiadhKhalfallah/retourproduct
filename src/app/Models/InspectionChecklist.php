<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionChecklist extends Model
{
    protected $fillable = [
        'item_id', 'package_condition', 'is_functional',
        'missing_parts_details', 'cleaned', 'inspected_at',
    ];

    protected $casts = [
        'is_functional' => 'boolean',
        'cleaned' => 'boolean',
        'inspected_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
