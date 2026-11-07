<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pallet_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pallet_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        // Reprend l'ancien document unique (disque public) comme pièce jointe.
        DB::table('pallets')->whereNotNull('document_path')->orderBy('id')->each(function ($p) {
            DB::table('pallet_attachments')->insert([
                'pallet_id' => $p->id,
                'disk' => 'public',
                'path' => $p->document_path,
                'original_name' => basename($p->document_path),
                'mime_type' => null,
                'size' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('pallets', function (Blueprint $table) {
            $table->dropColumn('document_path');
        });
    }

    public function down(): void
    {
        Schema::table('pallets', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('status');
        });

        Schema::dropIfExists('pallet_attachments');
    }
};
