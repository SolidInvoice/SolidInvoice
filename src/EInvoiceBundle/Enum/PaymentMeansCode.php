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
 * UNTDID 4461 payment means codes, restricted to the subset BT-81 uses.
 *
 * SOL-87 (SolidInvoice#2662) is the consumer that maps `PaymentInstructions` onto this enum.
 */
enum PaymentMeansCode: string
{
    case CreditTransfer = '30';
    case BankCard = '48';
    case StandingAgreement = '57';
    case SepaCreditTransfer = '58';
    case SepaDirectDebit = '59';
    case Report = '97';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditTransfer => 'Credit transfer',
            self::BankCard => 'Bank card',
            self::StandingAgreement => 'Standing agreement',
            self::SepaCreditTransfer => 'SEPA credit transfer',
            self::SepaDirectDebit => 'SEPA direct debit',
            self::Report => 'Report',
        };
    }
}
