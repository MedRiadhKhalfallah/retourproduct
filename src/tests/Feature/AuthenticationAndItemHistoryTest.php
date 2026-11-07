<?php

namespace Tests\Feature;

use App\Models\Pallet;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationAndItemHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_access_is_restricted_and_registration_is_not_available(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('pallets.index'))->assertRedirect(route('login'));
        $this->get(route('items.index'))->assertRedirect(route('login'));
        $this->post(route('pallets.store'), [])->assertRedirect(route('login'));
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $this->get(route('login'))->assertOk();
    }

    public function test_only_the_two_seeded_users_can_log_in_and_log_out(): void
    {
        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 2);

        $riadh = User::where('email', 'riadh@example.com')->firstOrFail();
        $this->assertSame('Riadh', $riadh->name);
        $this->assertTrue(Hash::check('RiadhRiadhRiadh123', $riadh->password));

        $this->post(route('login.store'), [
            'email' => 'ahmed@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'ahmed@example.com',
            'password' => 'Ahmed123!',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'ahmed@example.com')->firstOrFail());

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_item_creation_and_updates_are_recorded_with_user_and_displayed(): void
    {
        $user = User::factory()->create(['name' => 'Riadh']);
        $this->actingAs($user);

        $pallet = Pallet::create([
            'reference' => 'HISTORY-1',
            'supplier_name' => 'Supplier',
            'total_cost' => 100,
            'purchase_date' => now()->toDateString(),
        ]);
        $item = $pallet->items()->create([
            'name' => 'Lampe',
            'condition' => 'used',
            'status' => 'non_verifie',
        ]);

        $created = $item->histories()->firstOrFail();
        $this->assertSame('created', $created->action);
        $this->assertSame($user->id, $created->user_id);
        $this->assertStringContainsString('Article « Lampe » créé', $created->description);

        $item->update([
            'status' => 'pret_a_vendre',
            'asking_price' => 45,
            'inspection_notes' => 'Testée et fonctionnelle',
        ]);

        $updated = $item->histories()->firstOrFail();
        $this->assertSame('status_changed', $updated->action);
        $this->assertSame($user->id, $updated->user_id);
        $this->assertStringContainsString('Non vérifié', $updated->description);
        $this->assertStringContainsString('Prêt à vendre', $updated->description);
        $this->assertStringContainsString('45.00 €', $updated->description);
        $this->assertStringContainsString('Testée et fonctionnelle', $updated->description);

        $this->put(route('items.inspection', $item), [
            'package_condition' => 'intact',
            'is_functional' => '1',
            'missing_parts_details' => 'Aucune',
            'cleaned' => '1',
        ])->assertSessionHasNoErrors();

        $inspectionHistory = $item->histories()->firstOrFail();
        $this->assertSame('updated', $inspectionHistory->action);
        $this->assertSame($user->id, $inspectionHistory->user_id);
        $this->assertStringContainsString('Checklist d’inspection enregistrée', $inspectionHistory->description);
        $this->assertStringContainsString('Emballage: non défini → Intact', $inspectionHistory->description);
        $this->assertStringContainsString('Fonctionnel: Non testé → Oui', $inspectionHistory->description);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Historique des modifications')
            ->assertSee('Riadh')
            ->assertSee('Prêt à vendre');
    }

    public function test_item_page_defaults_to_edit_mode_with_read_only_toggle_and_unique_internal_reference(): void
    {
        $this->actingAs(User::factory()->create());
        $pallet = Pallet::create(['reference' => 'REF-1', 'supplier_name' => 'S', 'total_cost' => 10, 'purchase_date' => now()->toDateString()]);
        $item = $pallet->items()->create(['name' => 'Lampe', 'condition' => 'used', 'internal_reference' => 'STK-1']);
        $other = $pallet->items()->create(['name' => 'Table', 'condition' => 'used']);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('name="internal_reference"', false)
            ->assertSee('Workflow')
            ->assertSee('history-modal', false)
            ->assertDontSee('Mode copie rapide');

        $this->get(route('items.show', ['item' => $item, 'mode' => 'lecture']))
            ->assertOk()
            ->assertSee('Mode copie rapide')
            ->assertSee('STK-1')
            ->assertDontSee('Workflow')
            ->assertDontSee('history-modal', false);

        $this->put(route('items.update', $other), ['name' => 'Table', 'condition' => 'used', 'internal_reference' => 'STK-1'])
            ->assertSessionHasErrors('internal_reference');
        $this->put(route('items.update', $item), ['name' => 'Lampe', 'condition' => 'used', 'internal_reference' => 'STK-1'])
            ->assertSessionHasNoErrors();
    }

    public function test_database_seeder_can_run_repeatedly_without_duplicates(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, User::count());
        $this->assertSame(1, Pallet::where('reference', 'AMZ-0001')->count());
        $this->assertSame(3, Pallet::where('reference', 'AMZ-0001')->first()->items()->count());
    }

    public function test_history_user_is_set_null_when_user_is_deleted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $pallet = Pallet::create([
            'reference' => 'HISTORY-2',
            'supplier_name' => 'Supplier',
            'total_cost' => 100,
            'purchase_date' => now()->toDateString(),
        ]);
        $item = $pallet->items()->create(['name' => 'Chaise', 'condition' => 'used']);
        $history = $item->histories()->firstOrFail();

        $user->delete();

        $this->assertDatabaseHas('item_histories', [
            'id' => $history->id,
            'user_id' => null,
        ]);
    }

    public function test_items_can_be_assigned_to_the_two_users_and_filtered_from_dashboard(): void
    {
        $riadh = User::factory()->create(['email' => 'riadh@example.com', 'name' => 'Riadh']);
        $ahmed = User::factory()->create(['email' => 'ahmed@example.com', 'name' => 'Ahmed']);
        $otherUser = User::factory()->create();
        $this->actingAs($riadh);

        $pallet = Pallet::create([
            'reference' => 'ASSIGN-1',
            'supplier_name' => 'Supplier',
            'total_cost' => 100,
            'purchase_date' => now()->toDateString(),
        ]);

        $this->post(route('pallets.items.store', $pallet), [
            'name' => 'Produit Ahmed',
            'condition' => 'used',
            'assigned_to' => $ahmed->id,
            'supplier_product_url' => 'https://amazon.ca/dp/123',
        ])->assertSessionHasNoErrors();

        $this->post(route('pallets.items.store', $pallet), [
            'name' => 'Produit Riadh',
            'condition' => 'used',
            'assigned_to' => $riadh->id,
        ])->assertSessionHasNoErrors();

        $assignedItem = $pallet->items()->where('name', 'Produit Ahmed')->firstOrFail();
        $this->assertSame($ahmed->id, $assignedItem->assignedUser->id);
        $this->assertSame('https://amazon.ca/dp/123', $assignedItem->supplier_product_url);
        $createdHistory = $assignedItem->histories()->where('action', 'created')->firstOrFail();
        $this->assertStringContainsString('Responsable : Ahmed', $createdHistory->description);

        $this->get(route('dashboard', ['assigned_to' => $ahmed->id]))
            ->assertOk()
            ->assertSee('Produit Ahmed')
            ->assertDontSee('Produit Riadh');
        $this->get(route('dashboard', ['search' => 'amazon.ca/dp/123']))
            ->assertOk()
            ->assertSee('Produit Ahmed')
            ->assertDontSee('Produit Riadh');
        $this->get(route('items.show', $assignedItem))
            ->assertOk()
            ->assertSee('amazon.ca/dp/123')
            ->assertSee('Ahmed');

        $this->put(route('items.update', $assignedItem), [
            'name' => 'Produit Ahmed',
            'condition' => 'used',
            'assigned_to' => $otherUser->id,
        ])->assertSessionHasErrors('assigned_to');
    }
}
