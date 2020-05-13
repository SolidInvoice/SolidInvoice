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

namespace SolidInvoice\QuoteBundle\Tests\Cloner;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class QuoteClonerTest extends TestCase
{
    public function testClone()
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new \DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new \DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $quote = new Quote();
        $quote->setBaseTotal(new Money(123, $currency));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $quote->setDiscount($discount);
        $quote->setNotes('Notes');
        $quote->setTax(new Money(432, $currency));
        $quote->setTerms('Terms');
        $quote->setTotal(new Money(987, $currency));
        $quote->setClient($client);
        $quote->addItem($item);

        $dispatcher = new EventDispatcher();
        $quoteStateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new SingleStateMarkingStore('status'),
            $dispatcher,
            'quote'
        );

        $quoteCloner = new QuoteCloner($quoteStateMachine);

        $newQuote = $quoteCloner->clone($quote);

        $this->assertEquals($quote->getTotal(), $newQuote->getTotal());
        $this->assertEquals($quote->getBaseTotal(), $newQuote->getBaseTotal());
        $this->assertSame($quote->getDiscount(), $newQuote->getDiscount());
        $this->assertSame($quote->getNotes(), $newQuote->getNotes());
        $this->assertSame($quote->getTerms(), $newQuote->getTerms());
        $this->assertEquals($quote->getTax(), $newQuote->getTax());
        $this->assertSame($client, $newQuote->getClient());
        $this->assertSame(Graph::STATUS_DRAFT, $newQuote->getStatus());

        $this->assertNotSame($quote->getUuid(), $newQuote->getUuid());
        $this->assertNull($newQuote->getId());

        $this->assertCount(1, $newQuote->getItems());

        $quoteItem = $newQuote->getItems();
        $this->assertInstanceOf(Item::class, $quoteItem[0]);

        $this->assertSame($item->getTax(), $quoteItem[0]->getTax());
        $this->assertSame($item->getDescription(), $quoteItem[0]->getDescription());
        $this->assertInstanceOf(\DateTime::class, $quoteItem[0]->getCreated());
        $this->assertEquals($item->getPrice(), $quoteItem[0]->getPrice());
        $this->assertSame($item->getQty(), $quoteItem[0]->getQty());
    }
}
