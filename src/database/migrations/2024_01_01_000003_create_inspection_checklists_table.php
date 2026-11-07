<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('package_condition')->nullable(); // intact | abîmé | absent
            $table->boolean('is_functional')->nullable();
            $table->text('missing_parts_details')->nullable();
            $table->boolean('cleaned')->default(false);
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_checklists');
    }
};
