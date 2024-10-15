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

namespace SolidInvoice\InvoiceBundle\DataFixtures\ORM;

use Brick\Math\BigInteger;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use function assert;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $invoice = new Invoice();
        $client = $this->getReference('client');
        assert($client instanceof Client);

        $contact = $this->getReference('contact');
        assert($contact instanceof Contact);

        $invoice->setClient($client);
        $invoice->addUser($contact);
        $invoice->setStatus(Graph::STATUS_DRAFT);

        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setClient($client);
        $recurringInvoice->addUser($contact);
        $recurringInvoice->setStatus(Graph::STATUS_DRAFT);
        $recurringInvoice->setFrequency('* * * * *');
        $recurringInvoice->setDateStart(new DateTimeImmutable('2012-01-01 15:30:00', new DateTimeZone('Europe/Paris')));

        $item = new Line();
        $item->setQty(1);
        $item->setPrice(BigInteger::of(10000));
        $item->setDescription('Test Item');
        $invoice->addLine($item);

        $recurringItem = new Line();
        $recurringItem->setQty(1);
        $recurringItem->setPrice(BigInteger::of(10000));
        $recurringItem->setDescription('Test Item');
        $recurringInvoice->addItem($recurringItem);

        $this->setReference('invoice', $invoice);
        $this->setReference('recurringInvoice', $recurringInvoice);
        $this->setReference('invoiceItem', $item);
        $this->setReference('recurringInvoiceItem', $recurringItem);

        $manager->persist($item);
        $manager->persist($recurringItem);
        $manager->persist($invoice);
        $manager->persist($recurringInvoice);
        $manager->flush();
    }
}
