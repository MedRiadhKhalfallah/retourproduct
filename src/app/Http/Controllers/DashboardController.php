<?php

namespace App\Http\Controllers;

use App\Enums\ItemStatus;
use App\Models\Item;
use App\Models\Pallet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $pallets = Pallet::with('items')->latest('purchase_date')->get();
        $assignees = User::query()
            ->whereIn('email', User::ITEM_ASSIGNMENT_EMAILS)
            ->orderBy('name')
            ->get(['id', 'name']);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'assigned_to' => [
                'nullable',
                Rule::in([...$assignees->modelKeys(), 'unassigned']),
            ],
        ]);

        $byStatus = Item::selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $search = trim($filters['search'] ?? '');
        $assignedTo = $filters['assigned_to'] ?? null;
        $items = Item::with('pallet', 'assignedUser')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['name', 'original_sku', 'internal_reference', 'marketplace_url', 'supplier_product_url', 'inspection_notes', 'issues_description'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->when($assignedTo !== null, function ($query) use ($assignedTo) {
                if ($assignedTo === 'unassigned') {
                    $query->whereNull('assigned_to');
                } else {
                    $query->where('assigned_to', $assignedTo);
                }
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('dashboard', [
            'pallets' => $pallets,
            'items' => $items,
            'assignees' => $assignees,
            'filters' => $filters,
            'investment' => $pallets->sum('total_cost'),
            'revenue' => $pallets->sum->revenue,
            'byStatus' => $byStatus,
            'toVerify' => ($byStatus[ItemStatus::Unverified->value] ?? 0) + ($byStatus[ItemStatus::Inspecting->value] ?? 0),
            'ready' => $byStatus[ItemStatus::Ready->value] ?? 0,
            'sold' => $byStatus[ItemStatus::Sold->value] ?? 0,
        ]);
    }
}
