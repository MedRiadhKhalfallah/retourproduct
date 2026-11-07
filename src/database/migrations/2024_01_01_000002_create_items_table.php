<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pallet_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_sku')->nullable();
            $table->string('condition')->default('unknown'); // new | like_new | used | damaged | unknown
            $table->string('status')->default('unverified')->index(); // workflow d'inspection
            $table->string('sale_status')->default('in_stock')->index(); // in_stock|reserved|sold|discarded
            $table->decimal('purchase_cost_share', 10, 2)->default(0);
            $table->decimal('estimated_msrp', 10, 2)->nullable();
            $table->decimal('target_price', 10, 2)->nullable();
            $table->decimal('final_sale_price', 10, 2)->nullable();
            $table->date('sale_date')->nullable();
            $table->text('inspection_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
