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

namespace SolidInvoice\QuoteBundle\Tests\Cloner;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTime;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Model\Graph;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class QuoteClonerTest extends TestCase
{
    /**
     * @throws MathException
     */
    public function testClone(): void
    {
        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Line();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new DateTime('now'));
        $item->setPrice(BigInteger::of(120));
        $item->setQty(10);
        $item->setTotal(BigInteger::of(120 * 10));

        $quote = new Quote();
        $quote->setBaseTotal(BigInteger::of(123));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $quote->setDiscount($discount);
        $quote->setNotes('Notes');
        $quote->setQuoteId('bar-baz-foo');
        $quote->setTax(BigInteger::of(432));
        $quote->setTerms('Terms');
        $quote->setTotal(BigInteger::of(987));
        $quote->setClient($client);
        $quote->addLine($item);

        $dispatcher = new EventDispatcher();
        $quoteStateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'quote'
        );

        $config = $this->createMock(SystemConfig::class);
        $config->method('get')
            ->willReturn('generator');

        $billingIdGenerator = $this->createMock(IdGeneratorInterface::class);
        $billingIdGenerator->expects($this->once())
            ->method('generate')
            // ->with($quote, ['field' => 'quoteId'])
            ->willReturn('foo-bar-baz');

        $quoteCloner = new QuoteCloner(
            $quoteStateMachine,
            new BillingIdGenerator(
                new ServiceLocator(['generator' => fn () => $billingIdGenerator]),
                $config
            )
        );

        $newQuote = $quoteCloner->clone($quote);

        self::assertEquals($quote->getTotal(), $newQuote->getTotal());
        self::assertEquals($quote->getBaseTotal(), $newQuote->getBaseTotal());
        self::assertSame($quote->getDiscount(), $newQuote->getDiscount());
        self::assertSame($quote->getNotes(), $newQuote->getNotes());
        self::assertSame($quote->getTerms(), $newQuote->getTerms());
        self::assertNotSame($quote->getQuoteId(), $newQuote->getQuoteId());
        self::assertSame('generatorfoo-bar-bazgenerator', $newQuote->getQuoteId());
        self::assertEquals($quote->getTax(), $newQuote->getTax());
        self::assertSame($client, $newQuote->getClient());
        self::assertSame(Graph::STATUS_DRAFT, $newQuote->getStatus());

        self::assertNotSame($quote->getUuid(), $newQuote->getUuid());

        self::assertCount(1, $newQuote->getLines());

        $quoteItem = $newQuote->getLines();
        self::assertInstanceOf(Line::class, $quoteItem[0]);

        self::assertSame($item->getTax(), $quoteItem[0]->getTax());
        self::assertSame($item->getDescription(), $quoteItem[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $quoteItem[0]->getCreated());
        self::assertEquals($item->getPrice(), $quoteItem[0]->getPrice());
        self::assertSame($item->getQty(), $quoteItem[0]->getQty());
    }
}
