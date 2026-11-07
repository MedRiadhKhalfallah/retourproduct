<?php

namespace Database\Seeders;

use App\Models\Pallet;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $pallet = Pallet::firstOrCreate(
            ['reference' => 'AMZ-0001'],
            [
                'supplier_name' => 'Amazon Liquidation',
                'total_cost' => 450,
                'purchase_date' => now()->subDays(10),
                'notes' => 'Palette de démonstration',
            ],
        );

        // Les données de démo ne sont créées qu'une fois : un redéploiement ne modifie rien.
        if (! $pallet->wasRecentlyCreated) {
            return;
        }

        foreach ([
            ['name' => 'Aspirateur robot', 'original_sku' => 'B0001', 'estimated_msrp' => 300, 'asking_price' => 150, 'bottom_price' => 120],
            ['name' => 'Chaise de bureau Aosom', 'original_sku' => 'AO-22', 'estimated_msrp' => 180, 'asking_price' => 90, 'bottom_price' => 70],
            ['name' => 'Cafetière', 'original_sku' => 'B0003', 'estimated_msrp' => 120, 'asking_price' => 60, 'bottom_price' => 45],
        ] as $item) {
            $pallet->items()->firstOrCreate(['name' => $item['name']], $item);
        }

        $pallet->allocateCosts();

        $pallet->items()->orderBy('id')->first()
            ->update(['status' => 'vendu', 'final_sale_price' => 170, 'sale_date' => now()]);
    }
}
