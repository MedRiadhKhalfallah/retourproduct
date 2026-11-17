<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Models\Pallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_final_prices_per_responsible_and_average_for_all_known_sellers(): void
    {
        $firstSeller = User::factory()->create([
            'name' => 'Vendeur A',
            'email' => User::ITEM_ASSIGNMENT_EMAILS[0],
        ]);
        $secondSeller = User::factory()->create([
            'name' => 'Vendeur B',
            'email' => User::ITEM_ASSIGNMENT_EMAILS[1],
        ]);
        $this->actingAs($firstSeller);

        $pallet = Pallet::create([
            'reference' => 'PAL-1',
            'supplier_name' => 'Fournisseur A',
            'total_cost' => 100,
            'purchase_date' => now()->toDateString(),
        ]);
        $pallet->items()->create([
            'name' => 'Article vendu',
            'condition' => 'used',
            'status' => ItemStatus::Sold,
            'assigned_to' => $firstSeller->id,
            'purchase_cost_share' => 20,
            'asking_price' => 100,
            'bottom_price' => 10,
            'final_sale_price' => 80,
        ]);
        $pallet->items()->create([
            'name' => 'Article non vendu',
            'condition' => 'used',
            'status' => ItemStatus::Ready,
            'assigned_to' => $secondSeller->id,
            'asking_price' => 200,
            'bottom_price' => 90,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Prix de vente final')
            ->assertDontSee('Plancher')
            ->assertSee('Ventes finales par vendeur')
            ->assertSee('Vendeur A')
            ->assertSee('80.00 €')
            ->assertSee('Vendeur B')
            ->assertSee('0.00 €')
            ->assertSee(route('items.index', [
                'assigned_to' => $firstSeller->id,
                'search' => '',
                'status' => ItemStatus::Sold->value,
            ]))
            ->assertSee(route('items.index', [
                'assigned_to' => $secondSeller->id,
                'search' => '',
                'status' => ItemStatus::Sold->value,
            ]))
            ->assertSee('Revenu moyen par vendeur (Revenu généré ÷ 2 vendeurs)')
            ->assertSee('80,00 €')
            ->assertSee('40.00 €');

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('Prix de vente final')
            ->assertDontSee('Plancher')
            ->assertSee('80,00 €');
    }

    public function test_dashboard_displays_no_average_when_no_sellers_are_configured(): void
    {
        $this->actingAs(User::factory()->create());

        $pallet = Pallet::create([
            'reference' => 'PAL-2',
            'supplier_name' => 'Fournisseur B',
            'total_cost' => 50,
            'purchase_date' => now()->toDateString(),
        ]);
        $pallet->items()->create([
            'name' => 'Article vendu sans responsable',
            'condition' => 'used',
            'status' => ItemStatus::Sold,
            'final_sale_price' => 20,
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aucun vendeur configuré.')
            ->assertSee('Revenu moyen par vendeur (Revenu généré ÷ 0 vendeurs)')
            ->assertSee('—');
    }

    public function test_dashboard_page_size_selector_preserves_search_filters_and_rejects_oversized_values(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard', [
            'search' => 'Lampe',
            'assigned_to' => 'unassigned',
            'per_page' => 25,
        ]))
            ->assertOk()
            ->assertSee('value="25" selected', false)
            ->assertSee('type="hidden" name="per_page" value="25"', false)
            ->assertSee('type="hidden" name="search" value="Lampe"', false)
            ->assertSee('type="hidden" name="assigned_to" value="unassigned"', false);

        $this->get(route('dashboard', ['per_page' => 101]))
            ->assertSessionHasErrors('per_page');
    }
}
