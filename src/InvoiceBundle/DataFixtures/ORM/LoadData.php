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
