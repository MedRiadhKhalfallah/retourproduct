<?php

namespace App\Http\Controllers;

use App\Http\Requests\PalletRequest;
use App\Models\Pallet;

class PalletController extends Controller
{
    public function index()
    {
        return view('pallets.index', ['pallets' => Pallet::withCount('items')->with('items')->latest('purchase_date')->paginate(15)]);
    }

    public function create()
    {
        return view('pallets.create', ['pallet' => new Pallet(['purchase_date' => now(), 'status' => 'open'])]);
    }

    public function store(PalletRequest $request)
    {
        $pallet = Pallet::create($this->payload($request));
        $pallet->addAttachments($request->file('attachments', []));

        return redirect()->route('pallets.show', $pallet)->with('ok', 'Palette créée.');
    }

    public function show(Pallet $pallet)
    {
        $pallet->load('items.assignedUser', 'attachments');

        return view('pallets.show', [
            'pallet' => $pallet,
            'financialReport' => $pallet->financialReport(),
        ]);
    }

    public function edit(Pallet $pallet)
    {
        return view('pallets.edit', ['pallet' => $pallet->load('attachments')]);
    }

    public function update(PalletRequest $request, Pallet $pallet)
    {
        $pallet->update($this->payload($request));
        $pallet->addAttachments($request->file('attachments', []));
        $pallet->allocateCosts();

        return redirect()->route('pallets.show', $pallet)->with('ok', 'Palette mise à jour.');
    }

    public function destroy(Pallet $pallet)
    {
        $pallet->delete();

        return redirect()->route('pallets.index')->with('ok', 'Palette supprimée.');
    }

    private function payload(PalletRequest $request): array
    {
        $data = $request->safe()->except('attachments');
        $data['status'] ??= 'open';

        return $data;
    }
}
