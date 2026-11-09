<?php

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Pallet;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MarketplaceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private static function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    }

    private function pallet(): Pallet
    {
        return Pallet::create([
            'reference' => 'P-1', 'supplier_name' => 'X', 'total_cost' => 100, 'purchase_date' => now(),
        ]);
    }

    public function test_pallet_attachments_upload_view_download_and_scoping(): void
    {
        Storage::fake('local');
        $pallet = $this->pallet();
        $pdf = UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf');
        $pdfContents = $pdf->getContent();

        $this->post(route('pallets.attachments.store', $pallet), [
            'attachments' => [$pdf, self::png('recu.png')],
        ])->assertSessionHasNoErrors();

        $a = $pallet->attachments()->firstOrFail();
        $this->assertCount(2, $pallet->attachments);
        $this->assertSame(StoredFile::DATABASE_DISK, $a->disk);
        $this->assertStringStartsWith(StoredFile::PATH_PREFIX."pallets/{$pallet->id}/", $a->path);
        $storedFile = StoredFile::query()->where('path', $a->path)->firstOrFail();
        $this->assertSame(base64_encode($pdfContents), $storedFile->content_base64);

        $this->get(route('pallets.attachments.show', [$pallet, $a]))
            ->assertOk()
            ->assertStreamedContent($pdfContents);
        $this->get(route('pallets.attachments.download', [$pallet, $a]))
            ->assertOk()
            ->assertDownload('facture.pdf')
            ->assertStreamedContent($pdfContents);
        $this->get(route('pallets.show', $pallet))->assertOk()->assertSee('facture.pdf');

        $other = Pallet::create(['reference' => 'P-2', 'supplier_name' => 'Y', 'total_cost' => 1, 'purchase_date' => now()]);
        $this->get(route('pallets.attachments.download', [$other, $a]))->assertNotFound();

        $this->post(route('pallets.attachments.store', $pallet), [
            'attachments' => [UploadedFile::fake()->create('virus.exe', 5)],
        ])->assertSessionHasErrors('attachments.0');

        $this->delete(route('pallets.attachments.destroy', [$pallet, $a]))->assertRedirect();
        $this->assertModelMissing($storedFile);

        $legacyPath = "pallets/{$pallet->id}/legacy.pdf";
        $legacyContents = '%PDF legacy';
        Storage::disk('local')->put($legacyPath, $legacyContents);
        $legacyAttachment = $pallet->attachments()->create([
            'disk' => 'local',
            'path' => $legacyPath,
            'original_name' => 'legacy.pdf',
            'mime_type' => 'application/pdf',
            'size' => strlen($legacyContents),
        ]);

        $this->get(route('pallets.attachments.show', [$pallet, $legacyAttachment]))
            ->assertOk()
            ->assertStreamedContent($legacyContents);
        $this->delete(route('pallets.attachments.destroy', [$pallet, $legacyAttachment]))->assertRedirect();
        Storage::disk('local')->assertMissing($legacyPath);
    }

    public function test_item_create_with_photo_and_sale_rules(): void
    {
        $pallet = $this->pallet();
        $photo = self::png('p.png');
        $photoContents = $photo->getContent();

        $this->post(route('pallets.items.store', $pallet), [
            'name' => 'Lampe', 'condition' => 'used', 'status' => 'pret_a_vendre',
            'asking_price' => 50, 'bottom_price' => 30, 'marketplace_url' => 'kijiji.ca/abc',
            'inspection_notes' => 'Testée OK', 'issues_description' => 'Rayure',
            'image' => $photo,
        ])->assertSessionHasNoErrors();

        $item = Item::query()->firstOrFail();
        $this->assertSame('https://kijiji.ca/abc', $item->marketplace_url);
        $this->assertStringStartsWith(StoredFile::PATH_PREFIX.'items/', $item->image_path);
        $this->assertDatabaseHas('stored_files', [
            'path' => $item->image_path,
            'content_base64' => base64_encode($photoContents),
        ]);
        $this->assertStringContainsString('Rayure', $item->listing_description);
        $this->get(route('items.image', $item))->assertOk()->assertStreamedContent($photoContents);

        $this->put(route('items.update', $item), ['name' => 'Lampe', 'condition' => 'used', 'status' => 'vendu'])
            ->assertSessionHasErrors('final_sale_price');
        $this->put(route('items.update', $item), ['name' => 'Lampe', 'condition' => 'used', 'status' => 'vendu', 'final_sale_price' => 45])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($item->fresh()->sale_date);
        $this->assertSame(45.0, $pallet->fresh()->load('items')->revenue);

        $this->put(route('items.update', $item), ['name' => 'Lampe', 'condition' => 'used', 'asking_price' => 10, 'bottom_price' => 20, 'status' => 'pret_a_vendre'])
            ->assertSessionHasErrors('bottom_price');

        $this->get(route('items.show', ['item' => $item, 'mode' => 'lecture']))
            ->assertOk()
            ->assertSee('Copier')
            ->assertSee('Lampe')
            ->assertSee('src="'.route('items.image', $item, false).'"', false);
        $this->get(route('items.index'))->assertOk();
        $this->get('/')->assertOk();
    }

    public function test_replacing_or_deleting_an_item_removes_its_database_photo(): void
    {
        $pallet = $this->pallet();

        $this->post(route('pallets.items.store', $pallet), [
            'name' => 'Photo initiale',
            'condition' => 'used',
            'image' => self::png('initial.png'),
        ])->assertSessionHasNoErrors();

        $item = Item::query()->firstOrFail();
        $oldStoredFile = StoredFile::query()->where('path', $item->image_path)->firstOrFail();

        $this->put(route('items.update', $item), [
            'name' => 'Photo remplacée',
            'condition' => 'used',
            'image' => self::png('remplacement.png'),
        ])->assertSessionHasNoErrors();

        $item->refresh();
        $newStoredFile = StoredFile::query()->where('path', $item->image_path)->firstOrFail();
        $this->assertNotSame($oldStoredFile->path, $newStoredFile->path);
        $this->assertModelMissing($oldStoredFile);

        $this->delete(route('items.destroy', $item))
            ->assertRedirect(route('pallets.show', $pallet));
        $this->assertModelMissing($newStoredFile);
    }

    public function test_deleting_a_pallet_removes_database_backed_item_photos(): void
    {
        $pallet = $this->pallet();

        $this->post(route('pallets.items.store', $pallet), [
            'name' => 'Photo de palette',
            'condition' => 'used',
            'image' => self::png('palette.png'),
        ])->assertSessionHasNoErrors();

        $storedFile = StoredFile::query()->firstOrFail();

        $this->delete(route('pallets.destroy', $pallet))
            ->assertRedirect(route('pallets.index'));
        $this->assertModelMissing($storedFile);
    }

    public function test_item_image_endpoint_redirects_guests_to_login(): void
    {
        $storedFile = StoredFile::factory()->create(['mime_type' => 'image/png']);
        $item = $this->pallet()->items()->create([
            'name' => 'Photo privée',
            'image_path' => $storedFile->path,
        ]);

        $this->actingAsGuest()
            ->get(route('items.image', $item))
            ->assertRedirect(route('login'));
    }

    public function test_article_export_downloads_all_article_data_as_json_without_photo_contents(): void
    {
        $pallet = $this->pallet();
        $photoContent = 'photo data excluded from the text backup';
        $storedFile = StoredFile::factory()->create([
            'content_base64' => base64_encode($photoContent),
        ]);
        $firstItem = $pallet->items()->create([
            'name' => 'Article exporté',
            'original_sku' => 'SKU-EXPORT',
            'image_path' => $storedFile->path,
        ]);
        $secondItem = $pallet->items()->create(['name' => 'Second article']);

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('Exporter tous les articles (.txt)')
            ->assertSee(route('items.export'), false);

        $response = $this->get(route('items.export', ['search' => 'aucun résultat']));

        $response->assertOk()
            ->assertDownload()
            ->assertHeaderContains('Content-Disposition', 'articles-backup-')
            ->assertHeaderContains('Content-Disposition', '.txt');

        $content = $response->streamedContent();
        $backup = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $exportedItems = $backup['items'];

        $this->assertFalse($backup['photos_included']);
        $this->assertCount(2, $exportedItems);
        $this->assertSame([$firstItem->id, $secondItem->id], array_column($exportedItems, 'id'));
        $this->assertSame('Article exporté', $exportedItems[0]['name']);
        $this->assertSame($storedFile->path, $exportedItems[0]['image_path']);
        $this->assertArrayNotHasKey('content_base64', $exportedItems[0]);
        $this->assertStringNotContainsString(base64_encode($photoContent), $content);
    }

    public function test_article_export_redirects_guests_to_login(): void
    {
        $this->actingAsGuest()
            ->get(route('items.export'))
            ->assertRedirect(route('login'));
    }

    public function test_item_photo_preview_handles_missing_files_and_uses_a_relative_url(): void
    {
        Storage::fake('public');
        $pallet = $this->pallet();
        $item = $pallet->items()->create([
            'name' => 'Photo en attente',
            'image_path' => 'items/preview.jpg',
        ]);

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('Photo enregistrée introuvable. Téléversez-la à nouveau.');

        Storage::disk('public')->put('items/preview.jpg', 'image');

        $this->get(route('items.show', $item))
            ->assertOk()
            ->assertSee('src="/storage/items/preview.jpg"', false)
            ->assertDontSee('Photo enregistrée introuvable.');

        $missingDatabasePhoto = $pallet->items()->create([
            'name' => 'Photo base absente',
            'image_path' => StoredFile::PATH_PREFIX.'items/missing.png',
        ]);

        $this->get(route('items.show', $missingDatabasePhoto))
            ->assertOk()
            ->assertSee('Photo enregistrée introuvable. Téléversez-la à nouveau.');
        $this->get(route('items.image', $missingDatabasePhoto))->assertNotFound();
    }

    public function test_pallet_financial_report_uses_allocated_costs_actual_revenue_and_unsold_targets(): void
    {
        $pallet = $this->pallet();
        $assignee = User::factory()->create(['name' => 'Ahmed']);

        $pallet->items()->createMany([
            [
                'name' => 'Vendu à perte',
                'condition' => 'used',
                'status' => 'vendu',
                'estimated_msrp' => 300,
                'asking_price' => 100,
                'final_sale_price' => 50,
                'sale_date' => now()->toDateString(),
            ],
            [
                'name' => 'Encore en stock',
                'condition' => 'used',
                'status' => 'pret_a_vendre',
                'estimated_msrp' => 100,
                'asking_price' => 40,
                'assigned_to' => $assignee->id,
            ],
        ]);
        $pallet->allocateCosts();
        $pallet->load('items.assignedUser');

        $report = $pallet->financialReport();
        $this->assertSame(100.0, $report['purchase_total']);
        $this->assertSame(50.0, $report['revenue_total']);
        $this->assertSame(50.0, $report['average_purchase_price']);
        $this->assertSame(50.0, $report['average_sale_price']);
        $this->assertSame(-50.0, $report['net_profit']);

        $sold = $report['items']->firstWhere('item.name', 'Vendu à perte');
        $unsold = $report['items']->firstWhere('item.name', 'Encore en stock');
        $this->assertSame(75.0, $sold['purchase_price']);
        $this->assertSame(50.0, $sold['sale_price']);
        $this->assertSame(-25.0, $sold['profit']);
        $this->assertFalse($sold['sale_is_estimate']);
        $this->assertSame(25.0, $unsold['purchase_price']);
        $this->assertSame(40.0, $unsold['sale_price']);
        $this->assertSame(15.0, $unsold['profit']);
        $this->assertTrue($unsold['sale_is_estimate']);

        $this->get(route('pallets.show', $pallet))
            ->assertOk()
            ->assertSee('Rapport financier par palette')
            ->assertSee('Vendu à perte')
            ->assertSee('Encore en stock')
            ->assertSee('Ahmed')
            ->assertSee('text-rose-600')
            ->assertSee('Bilan global de la palette')
            ->assertSee('Perte');
    }

    #[DataProvider('zeroResaleValueStatuses')]
    public function test_discarded_or_incomplete_items_are_valued_at_zero_and_report_losses(ItemStatus $status): void
    {
        $pallet = $this->pallet();
        $item = $pallet->items()->create([
            'name' => 'Article à valeur nulle',
            'condition' => 'used',
            'status' => $status->value,
            'estimated_msrp' => 100,
            'asking_price' => 75,
            'final_sale_price' => 25,
        ]);
        $pallet->allocateCosts();
        $item = $item->fresh();
        $pallet->load('items');

        $report = $pallet->financialReport();
        $row = $report['items']->firstWhere('item.id', $item->id);

        $this->assertSame(0.0, $item->sale_value);
        $this->assertSame(-100.0, $item->margin);
        $this->assertSame(-100.0, $item->expected_margin);
        $this->assertSame(0.0, $report['revenue_total']);
        $this->assertSame(-100.0, $report['net_profit']);
        $this->assertSame(0.0, $row['sale_price']);
        $this->assertSame(-100.0, $row['profit']);
        $this->assertFalse($row['sale_is_estimate']);
        $this->assertSame('Valeur retenue', $row['sale_label']);
        $this->assertSame('Perte calculée', $row['profit_label']);

        $this->get(route('pallets.show', $pallet))
            ->assertOk()
            ->assertSee('Valeur retenue')
            ->assertSee('Perte calculée')
            ->assertSee('Perte -100,00 €');
    }

    public static function zeroResaleValueStatuses(): array
    {
        return [
            'discarded' => [ItemStatus::Discarded],
            'incomplete pieces' => [ItemStatus::Incomplete],
        ];
    }

    #[DataProvider('zeroResaleValueStatuses')]
    public function test_item_details_show_zero_resale_value_as_a_loss(ItemStatus $status): void
    {
        $item = $this->pallet()->items()->create([
            'name' => 'Article à valeur nulle',
            'condition' => 'used',
            'status' => $status->value,
            'purchase_cost_share' => 50,
            'asking_price' => 75,
        ]);

        $this->get(route('items.show', ['item' => $item, 'mode' => 'lecture']))
            ->assertOk()
            ->assertSee('Valeur retenue')
            ->assertSee('0,00 €')
            ->assertSee('Perte calculée')
            ->assertSee('-50,00 €');
    }

    public function test_stock_filters_by_assignee_and_pages_show_breadcrumbs(): void
    {
        $pallet = $this->pallet();
        $riadh = User::factory()->create(['name' => 'Riadh', 'email' => 'riadh@example.com']);
        $pallet->items()->create(['name' => 'Article de Riadh', 'assigned_to' => $riadh->id]);
        $pallet->items()->create(['name' => 'Article libre']);

        $this->get(route('items.index', ['assigned_to' => $riadh->id]))
            ->assertOk()
            ->assertSee('Article de Riadh')
            ->assertDontSee('Article libre')
            ->assertSee('Dashboard Financier');

        $this->get(route('items.index', ['assigned_to' => 999]))->assertSessionHasErrors('assigned_to');
        $this->get(route('items.show', $pallet->items()->first()))
            ->assertSee('aria-current="page"', false)
            ->assertSee(route('pallets.show', $pallet), false);
    }

    #[DataProvider('itemSearchFields')]
    public function test_stock_search_finds_items_by_name_sku_reference_or_id(string $field): void
    {
        $pallet = $this->pallet();
        $item = $pallet->items()->create([
            'name' => 'Lampe à poser unique',
            'original_sku' => 'SKU-LAMPE-UNIQUE',
            'internal_reference' => 'REF-LAMPE-UNIQUE',
        ]);
        $pallet->items()->create([
            'name' => 'Fauteuil sans correspondance',
            'original_sku' => 'SKU-FAUTEUIL',
            'internal_reference' => 'REF-FAUTEUIL',
        ]);

        $search = match ($field) {
            'name' => $item->name,
            'sku' => $item->original_sku,
            'reference' => $item->internal_reference,
            'id' => (string) $item->id,
        };

        $this->get(route('items.index', ['search' => $search]))
            ->assertOk()
            ->assertSee('Lampe à poser unique')
            ->assertDontSee('Fauteuil sans correspondance')
            ->assertSee('1 article');
    }

    public static function itemSearchFields(): array
    {
        return [
            'name' => ['name'],
            'SKU' => ['sku'],
            'internal reference' => ['reference'],
            'database ID' => ['id'],
        ];
    }

    public function test_stock_cards_escape_article_names_and_references(): void
    {
        $pallet = $this->pallet();
        $dangerousContent = "<script>alert('xss')</script>";
        $pallet->items()->create([
            'name' => 'Lampe '.$dangerousContent,
            'original_sku' => $dangerousContent,
            'internal_reference' => $dangerousContent,
        ]);

        $this->get(route('items.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false);
    }

    public function test_stock_search_escapes_query_input_and_does_not_inject_a_match(): void
    {
        $pallet = $this->pallet();
        $pallet->items()->create(['name' => 'Article visible sans filtre']);
        $search = "' OR 1=1 -- <script>alert('xss')</script>";

        $this->get(route('items.index', ['search' => $search]))
            ->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>', false)
            ->assertDontSee('Article visible sans filtre')
            ->assertSee('Aucun article ne correspond');
    }

    #[DataProvider('invalidSearchTerms')]
    public function test_stock_search_rejects_non_string_or_overlong_terms(string|array $search): void
    {
        $this->get(route('items.index', ['search' => $search]))
            ->assertSessionHasErrors('search');
    }

    public static function invalidSearchTerms(): array
    {
        return [
            'array input' => [['article', 'sku']],
            'over 255 characters' => [str_repeat('x', 256)],
        ];
    }
}
