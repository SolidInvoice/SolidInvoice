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

namespace SolidInvoice\InvoiceBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

#[CoversClass(Invoice::class)]
#[CoversClass(CreditNote::class)]
final class InvoiceTypeCodeDefaultTest extends TestCase
{
    public function testInvoiceDefaultsToCommercialInvoice(): void
    {
        self::assertSame(InvoiceTypeCode::CommercialInvoice, new Invoice()->getInvoiceTypeCode());
    }

    public function testInvoiceTypeCodeIsSettable(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceTypeCode(InvoiceTypeCode::CorrectedInvoice);

        self::assertSame(InvoiceTypeCode::CorrectedInvoice, $invoice->getInvoiceTypeCode());
    }

    public function testCreditNoteDefaultsToCreditNote(): void
    {
        self::assertSame(InvoiceTypeCode::CreditNote, new CreditNote()->getInvoiceTypeCode());
    }

    public function testCreditNoteInvoiceTypeCodeIsSettable(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setInvoiceTypeCode(InvoiceTypeCode::CorrectedInvoice);

        self::assertSame(InvoiceTypeCode::CorrectedInvoice, $creditNote->getInvoiceTypeCode());
    }
}
