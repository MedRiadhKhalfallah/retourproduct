<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('supplier_name');
            $table->decimal('total_cost', 10, 2);
            $table->date('purchase_date');
            $table->string('status')->default('open'); // open | closed
            $table->string('document_path')->nullable(); // photo / facture
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pallets');
    }
};
