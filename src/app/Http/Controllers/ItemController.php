<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\Pallet;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $assignees = User::query()->whereIn('email', User::ITEM_ASSIGNMENT_EMAILS)->orderBy('name')->get(['id', 'name']);
        $request->validate([
            'status' => ['nullable', Rule::enum(ItemStatus::class)],
            'assigned_to' => ['nullable', Rule::in([...$assignees->modelKeys(), 'unassigned'])],
        ]);

        $items = Item::with('pallet', 'assignedUser')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->assigned_to, fn ($q, $a) => $a === 'unassigned' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $a))
            ->latest()->paginate(20)->withQueryString();

        return view('items.index', compact('items', 'assignees'));
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
        $item = $pallet->items()->create($this->payload($request));
        if ($request->hasFile('image')) {
            $item->update(['image_path' => $item->storeImage($request->file('image'))]);
        }
        $pallet->allocateCosts();

        return redirect()->route('items.show', $item)->with('ok', 'Article ajouté.');
    }

    public function show(Request $request, Item $item)
    {
        return view('items.show', [
            'item' => $item->load('pallet', 'checklist', 'histories.user', 'assignedUser'),
            'assignees' => $this->assignmentUsers(),
            'readOnly' => $request->query('mode') === 'lecture',
        ]);
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
        $data = $this->payload($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $item->storeImage($request->file('image'));
        }
        $item->update($data);
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
