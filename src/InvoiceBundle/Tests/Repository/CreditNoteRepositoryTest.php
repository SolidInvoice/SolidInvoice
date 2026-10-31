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

namespace SolidInvoice\InvoiceBundle\Tests\Repository;

use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CreditNoteRepository::class)]
final class CreditNoteRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private CreditNoteRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');

        $this->repository = new CreditNoteRepository($registry);
    }

    public function testGetTotalCreditedForInvoiceReturnsZeroWhenNoCreditNotesExist(): void
    {
        $invoice = InvoiceFactory::createOne(['company' => $this->company]);

        $total = $this->repository->getTotalCreditedForInvoice($invoice);

        self::assertTrue(BigInteger::zero()->isEqualTo($total));
    }
}
