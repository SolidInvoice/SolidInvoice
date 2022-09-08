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

namespace SolidInvoice\QuoteBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Money\Currency;
use Money\Money;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $quote = new Quote();
        $quote->setClient($this->getReference('client'));
        $quote->addUser($this->getReference('contact'));
        $quote->setStatus(Graph::STATUS_DRAFT);

        $item = new Item();
        $item->setQty(1);
        $item->setPrice(new Money(10000, new Currency('USD')));
        $item->setDescription('Test Item');
        $quote->addItem($item);

        $this->setReference('quote', $quote);
        $this->setReference('quoteItem', $item);

        $manager->persist($item);
        $manager->persist($quote);
        $manager->flush();
    }
}
