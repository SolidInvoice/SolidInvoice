<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\InvoiceBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Money\Currency;
use Money\Money;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Traits\InvoiceStatusTrait;

class LoadData extends Fixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $invoice = new Invoice();
        $invoice->setClient($this->getReference('client'));
        $invoice->addUser($this->getReference('contact'));
        $invoice->setStatus(Graph::STATUS_DRAFT);

        $item = new Item();
        $item->setQty(1);
        $item->setPrice(new Money(10000, new Currency('USD')));
        $item->setDescription('Test Item');
        $invoice->addItem($item);

        $this->setReference('invoice', $invoice);
        $this->setReference('invoiceItem', $item);

        $manager->persist($item);
        $manager->persist($invoice);
        $manager->flush();
    }
}
