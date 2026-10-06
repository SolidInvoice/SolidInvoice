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

namespace SolidInvoice\TaxBundle\Tests\Functional;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\TaxBundle\Calculator\TaxCalculatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * {@see TaxCalculatorInterface::calculate()} runs on the render path — see
 * {@see \SolidInvoice\TaxBundle\Twig\Extension\TaxBreakdownExtension::taxBreakdown()} — so a
 * plain read must never leave a line scheduled for an `UPDATE`, and must never move its
 * `updated` timestamp.
 *
 * @see Line::updateTotal()
 */
#[Group('functional')]
final class TaxCalculatorDirtyTrackingTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testCallingCalculateOnAFreshlyLoadedInvoiceSchedulesNoLineUpdate(): void
    {
        $invoiceId = $this->persistInvoiceWithLines();

        $this->em->clear();
        $invoice = $this->em->find(Invoice::class, $invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        $calculator = self::getContainer()->get(TaxCalculatorInterface::class);
        $calculator->calculate($invoice);

        $this->em->getUnitOfWork()->computeChangeSets();

        foreach ($this->em->getUnitOfWork()->getScheduledEntityUpdates() as $scheduled) {
            self::assertNotInstanceOf(Line::class, $scheduled, 'Reading the invoice must not schedule a line update');
        }
    }

    public function testCallingCalculateOnAFreshlyLoadedInvoiceLeavesUpdatedUnchanged(): void
    {
        $invoiceId = $this->persistInvoiceWithLines();

        $this->em->clear();
        $invoice = $this->em->find(Invoice::class, $invoiceId);
        self::assertInstanceOf(Invoice::class, $invoice);

        $updatedBefore = [];

        foreach ($invoice->getLines() as $line) {
            $updatedBefore[(string) $line->getId()] = $line->getUpdated();
        }

        $calculator = self::getContainer()->get(TaxCalculatorInterface::class);
        $calculator->calculate($invoice);

        $this->em->flush();

        $this->em->clear();

        $reloaded = $this->em->find(Invoice::class, $invoiceId);
        self::assertInstanceOf(Invoice::class, $reloaded);

        foreach ($reloaded->getLines() as $line) {
            self::assertEquals(
                $updatedBefore[(string) $line->getId()],
                $line->getUpdated(),
                "Reading the invoice must not bump the line's updated timestamp"
            );
        }
    }

    private function persistInvoiceWithLines(): string
    {
        $invoice = new Invoice();
        $invoice->setClient(ClientFactory::createOne(['currencyCode' => 'USD']));
        $invoice->setStatus(InvoiceStatus::Draft);

        $line = new Line();
        $line->setDescription('Consulting')
            ->setPrice(1000)
            ->setQty(2);

        $invoice->addLine($line);

        $this->em->persist($invoice);
        $this->em->flush();

        $id = (string) $invoice->getId();

        // Force the updated timestamp Gedmo set on insert into the past, portably across
        // the test suite's database backends, so a false bump on the read path under test
        // is not hidden by both timestamps landing in the same second.
        $this->em->createQuery('UPDATE ' . Line::class . ' l SET l.updated = :updated WHERE l.invoice = :invoice')
            ->setParameter('updated', CarbonImmutable::now()->subDays(1))
            ->setParameter('invoice', $invoice)
            ->execute();
        $this->em->clear();

        return $id;
    }
}
