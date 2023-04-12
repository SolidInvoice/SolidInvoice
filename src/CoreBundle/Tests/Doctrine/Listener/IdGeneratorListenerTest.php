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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Doctrine\Listener\IdGeneratorListener;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\CoreBundle\Doctrine\Listener\IdGeneratorListener */
final class IdGeneratorListenerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGetSubscribedEvents(): void
    {
        $listener = new IdGeneratorListener($this->createMock(ManagerRegistry::class));

        self::assertSame([Events::prePersist], $listener->getSubscribedEvents());
    }

    public function testPrePersist(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);

        $registry = self::getContainer()->get('doctrine');
        $entityManager = $registry->getManager();
        assert($entityManager instanceof EntityManagerInterface);

        $listener = new IdGeneratorListener($registry);

        $invoice = new Invoice();
        $quote = new Quote();
        $listener->prePersist(new PrePersistEventArgs($invoice, $entityManager));
        $listener->prePersist(new PrePersistEventArgs($quote, $entityManager));

        self::assertSame('1', $invoice->getInvoiceId());
        self::assertSame('1', $quote->getQuoteId());

        InvoiceFactory::createMany(3, ['company' => $this->company, 'client' => $client]);

        $invoice = new Invoice();
        $quote = new Quote();
        $listener->prePersist(new PrePersistEventArgs($invoice, $entityManager));
        $listener->prePersist(new PrePersistEventArgs($quote, $entityManager));

        self::assertSame('4', $invoice->getInvoiceId());
        self::assertSame('1', $quote->getQuoteId());
    }
}
