<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Tests\Twig\Components;

use Brick\Math\Exception\MathException;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Twig\Components\CreateQuote;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

final class CreateQuoteTest extends LiveComponentTest
{
    use Factories;

    public function testCreateQuote(): void
    {
        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: [
                'quote' => new Quote(),
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @throws MathException
     */
    public function testCreateQuoteWithMultipleLines(): void
    {
        $quote = new Quote();
        $quote->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();
        $quote->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: [
                'quote' => $quote,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @throws MathException
     */
    public function testCreateQuoteWithTaxRates(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $tax = (new Tax())
            ->setName('VAT')
            ->setRate(20)
            ->setType(Tax::TYPE_INCLUSIVE);

        $em->persist($tax);

        (function (): void {
            /** @var Tax $this */
            $this->id = Ulid::fromString('0f9e91e6-06ba-11ef-a331-5a2cf21a5680'); // @phpstan-ignore-line
        })(...)->call($tax);

        $em->flush();

        $quote = new Quote();
        $quote->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();

        $component = $this->createLiveComponent(
            name: CreateQuote::class,
            data: [
                'quote' => $quote,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }
}
