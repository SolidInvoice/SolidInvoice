<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\EInvoiceBundle\Enum;

/**
 * UNTDID 5305 duty/tax/fee category codes, restricted to the subset EN 16931 uses for BT-95,
 * BT-102, BT-118 and BT-151. This is a closed, published list, so it is complete from day one.
 *
 * SOL-95 (SolidInvoice#2664) extends `TaxBundle\Enum\TaxCategory` to reach the cases the tax
 * engine cannot currently produce.
 */
enum VatCategoryCode: string
{
    case StandardRate = 'S';
    case ZeroRated = 'Z';
    case Exempt = 'E';
    case ReverseCharge = 'AE';
    case IntraCommunitySupply = 'K';
    case FreeExportItem = 'G';
    case OutsideScope = 'O';
    case CanaryIslandsTax = 'L';
    case CeutaMelillaTax = 'M';

    public function getLabel(): string
    {
        return match ($this) {
            self::StandardRate => 'Standard rate',
            self::ZeroRated => 'Zero rated goods',
            self::Exempt => 'Exempt from tax',
            self::ReverseCharge => 'VAT reverse charge',
            self::IntraCommunitySupply => 'VAT exempt for EEA intra-community supply',
            self::FreeExportItem => 'Free export item, VAT not charged',
            self::OutsideScope => 'Services outside scope of tax',
            self::CanaryIslandsTax => 'Canary Islands general indirect tax',
            self::CeutaMelillaTax => 'Tax for production, services and importation in Ceuta and Melilla',
        };
    }
}
