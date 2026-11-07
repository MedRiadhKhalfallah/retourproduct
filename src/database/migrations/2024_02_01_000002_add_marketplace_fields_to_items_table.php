<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('target_price', 'asking_price');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->date('listing_date')->nullable()->after('estimated_msrp');
            $table->string('marketplace_url', 500)->nullable()->after('listing_date');
            $table->decimal('bottom_price', 10, 2)->nullable()->after('asking_price');
            $table->string('image_path')->nullable()->after('sale_date');
            $table->text('issues_description')->nullable()->after('inspection_notes');
        });

        // Fusion de l'ancien statut d'inspection et du statut de vente en un seul statut.
        $map = [
            'unverified' => 'non_verifie',
            'inspecting' => 'en_cours_inspection',
            'ready' => 'pret_a_vendre',
            'damaged' => 'en_reparation',
            'parts' => 'incomplet_pieces',
        ];
        foreach ($map as $old => $new) {
            DB::table('items')->where('status', $old)->update(['status' => $new]);
        }
        DB::table('items')->where('sale_status', 'sold')->update(['status' => 'vendu']);
        DB::table('items')->where('sale_status', 'discarded')->update(['status' => 'jete']);

        Schema::table('items', function (Blueprint $table) {
            $table->string('status')->default('non_verifie')->change();
            $table->dropIndex(['sale_status']);
            $table->dropColumn('sale_status');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sale_status')->default('in_stock')->index();
        });

        DB::table('items')->where('status', 'vendu')->update(['sale_status' => 'sold', 'status' => 'ready']);
        DB::table('items')->where('status', 'jete')->update(['sale_status' => 'discarded', 'status' => 'damaged']);

        $map = [
            'non_verifie' => 'unverified',
            'en_cours_inspection' => 'inspecting',
            'pret_a_vendre' => 'ready',
            'en_reparation' => 'damaged',
            'incomplet_pieces' => 'parts',
        ];
        foreach ($map as $new => $old) {
            DB::table('items')->where('status', $new)->update(['status' => $old]);
        }

        Schema::table('items', function (Blueprint $table) {
            $table->string('status')->default('unverified')->change();
            $table->dropColumn(['listing_date', 'marketplace_url', 'bottom_price', 'image_path', 'issues_description']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('asking_price', 'target_price');
        });
    }
};
