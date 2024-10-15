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

        $line = new Line();
        $line->setQty(1);
        $line->setPrice(BigInteger::of(10000));
        $line->setDescription('Test Line');
        $invoice->addLine($line);

        $recurringLine = new Line();
        $recurringLine->setQty(1);
        $recurringLine->setPrice(BigInteger::of(10000));
        $recurringLine->setDescription('Test Line');
        $recurringInvoice->addLine($recurringLine);

        $this->setReference('invoice', $invoice);
        $this->setReference('recurringInvoice', $recurringInvoice);
        $this->setReference('invoiceLine', $line);
        $this->setReference('recurringInvoiceLine', $recurringLine);

        $manager->persist($line);
        $manager->persist($recurringLine);
        $manager->persist($invoice);
        $manager->persist($recurringInvoice);
        $manager->flush();
    }
}
