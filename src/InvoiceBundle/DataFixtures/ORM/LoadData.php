<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Money\Currency;
use Money\Money;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $invoice = new Invoice();
        $invoice->setClient($this->getReference('client'));
        $invoice->addUser($this->getReference('contact'));
        $invoice->setStatus(Graph::STATUS_DRAFT);

        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setClient($this->getReference('client'));
        $recurringInvoice->addUser($this->getReference('contact'));
        $recurringInvoice->setStatus(Graph::STATUS_DRAFT);
        $recurringInvoice->setFrequency('* * * * *');
        $recurringInvoice->setDateStart(new \DateTimeImmutable('2012-01-01 15:30:00'));

        $item = new Item();
        $item->setQty(1);
        $item->setPrice(new Money(10000, new Currency('USD')));
        $item->setDescription('Test Item');
        $invoice->addItem($item);

        $recurringItem = new Item();
        $recurringItem->setQty(1);
        $recurringItem->setPrice(new Money(10000, new Currency('USD')));
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
