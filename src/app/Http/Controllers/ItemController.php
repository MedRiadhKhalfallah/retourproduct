<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\Pallet;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $assignees = User::query()->whereIn('email', User::ITEM_ASSIGNMENT_EMAILS)->orderBy('name')->get(['id', 'name']);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
            'assigned_to' => ['nullable', Rule::in([...$assignees->modelKeys(), 'unassigned'])],
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? null;
        $assignedTo = $filters['assigned_to'] ?? null;

        $items = Item::with('pallet', 'assignedUser', 'storedImage:id,path')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query->where('name', 'like', $term)
                        ->orWhere('original_sku', 'like', $term)
                        ->orWhere('internal_reference', 'like', $term);

                    if (ctype_digit($search)) {
                        $query->orWhereKey((int) $search);
                    }
                });
            })
            ->when($status, function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($assignedTo, function (Builder $query) use ($assignedTo): void {
                if ($assignedTo === 'unassigned') {
                    $query->whereNull('assigned_to');

                    return;
                }

                $query->where('assigned_to', $assignedTo);
            })
            ->latest()->paginate(20)->withQueryString();

        return view('items.index', compact('items', 'assignees', 'search'));
    }

    public function export(): StreamedResponse
    {
        $exportedAt = now();
        $json = json_encode([
            'exported_at' => $exportedAt->toIso8601String(),
            'photos_included' => false,
            'items' => Item::query()->orderBy('id')->get()->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return response()->streamDownload(
            static function () use ($json): void {
                echo $json;
            },
            'articles-backup-'.$exportedAt->format('Y-m-d_H-i-s').'.txt',
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }

    public function create(Pallet $pallet)
    {
        return view('items.create', [
            'pallet' => $pallet,
            'item' => new Item(['condition' => 'unknown']),
            'assignees' => $this->assignmentUsers(),
        ]);
    }

    public function store(ItemRequest $request, Pallet $pallet)
    {
        $item = DB::transaction(function () use ($request, $pallet): Item {
            $item = $pallet->items()->create($this->payload($request));

            if ($request->hasFile('image')) {
                $item->update(['image_path' => $item->storeImage($request->file('image'))]);
            }

            return $item;
        });
        $pallet->allocateCosts();

        return redirect()->route('items.show', $item)->with('ok', 'Article ajouté.');
    }

    public function show(Request $request, Item $item)
    {
        return view('items.show', [
            'item' => $item->load('pallet', 'checklist', 'histories.user', 'assignedUser', 'storedImage:id,path'),
            'assignees' => $this->assignmentUsers(),
            'readOnly' => $request->query('mode') === 'lecture',
        ]);
    }

    public function image(Item $item): StreamedResponse
    {
        abort_unless(str_starts_with((string) $item->image_path, StoredFile::PATH_PREFIX), 404);

        $storedFile = $item->storedImage()->firstOrFail();
        $content = base64_decode($storedFile->content_base64, true);
        abort_if($content === false, 500);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            basename($storedFile->path),
            ['Content-Type' => $storedFile->mime_type ?: 'application/octet-stream'],
            'inline',
        );
    }

    public function edit(Item $item)
    {
        return redirect()->route('items.show', $item);
    }

    private function assignmentUsers(): Collection
    {
        return User::query()
            ->whereIn('email', User::ITEM_ASSIGNMENT_EMAILS)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function update(ItemRequest $request, Item $item)
    {
        $previousImagePath = $item->image_path;

        DB::transaction(function () use ($request, $item): void {
            $data = $this->payload($request);

            if ($request->hasFile('image')) {
                $data['image_path'] = $item->storeImage($request->file('image'));
            }

            $item->update($data);
        });

        if ($request->hasFile('image')) {
            $item->deleteStoredImage($previousImagePath);
        }

        $item->pallet->allocateCosts();

        return redirect()->route('items.show', $item)->with('ok', 'Article mis à jour.');
    }

    private function payload(ItemRequest $request): array
    {
        $data = $request->safe()->except('image');

        if (($data['status'] ?? null) === ItemStatus::Sold->value) {
            $data['sale_date'] ??= now()->toDateString();
        } elseif (array_key_exists('status', $data)) {
            // Un article non vendu ne conserve pas de données de vente
            $data['final_sale_price'] = null;
            $data['sale_date'] = null;
        }

        return $data;
    }

    public function destroy(Item $item)
    {
        $pallet = $item->pallet;
        $item->delete();
        $pallet->allocateCosts();

        return redirect()->route('pallets.show', $pallet)->with('ok', 'Article supprimé.');
    }

    public function transition(Request $request, Item $item)
    {
        $to = ItemStatus::tryFrom((string) $request->input('status'));

        if (! $to || ! $item->transitionTo($to)) {
            return back()->withErrors(['status' => 'Transition non autorisée.']);
        }

        return back()->with('ok', 'Statut : '.$to->label());
    }
}
