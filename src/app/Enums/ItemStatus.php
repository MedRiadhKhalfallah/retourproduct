<?php

namespace App\Enums;

enum ItemStatus: string
{
    case Unverified = 'non_verifie';
    case Inspecting = 'en_cours_inspection';
    case Ready = 'pret_a_vendre';
    case Repair = 'en_reparation';
    case Sold = 'vendu';
    case Incomplete = 'incomplet_pieces';
    case Discarded = 'jete';

    public function label(): string
    {
        return match ($this) {
            self::Unverified => 'Non vérifié',
            self::Inspecting => "En cours d'inspection",
            self::Ready => 'Prêt à vendre',
            self::Repair => 'En réparation',
            self::Sold => 'Vendu',
            self::Incomplete => 'Incomplet / pièces',
            self::Discarded => 'Jeté',
        };
    }

    /** Classes Tailwind du badge. */
    public function badge(): string
    {
        return match ($this) {
            self::Unverified => 'bg-gray-100 text-gray-700 ring-gray-300',
            self::Inspecting => 'bg-amber-100 text-amber-800 ring-amber-300',
            self::Ready => 'bg-emerald-100 text-emerald-800 ring-emerald-300',
            self::Repair => 'bg-orange-100 text-orange-800 ring-orange-300',
            self::Sold => 'bg-blue-100 text-blue-800 ring-blue-300',
            self::Incomplete => 'bg-purple-100 text-purple-800 ring-purple-300',
            self::Discarded => 'bg-red-100 text-red-800 ring-red-300',
        };
    }

    /** Transitions rapides du workflow (la vente se saisit via le formulaire : prix final requis). */
    public function next(): array
    {
        return match ($this) {
            self::Unverified => [self::Inspecting],
            self::Inspecting => [self::Ready, self::Repair, self::Incomplete],
            self::Repair, self::Incomplete => [self::Inspecting, self::Ready, self::Discarded],
            self::Ready => [self::Inspecting, self::Discarded],
            self::Sold, self::Discarded => [self::Inspecting],
        };
    }
}
