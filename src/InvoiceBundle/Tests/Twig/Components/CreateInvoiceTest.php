<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Twig\Components;

use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

final class CreateInvoiceTest extends LiveComponentTest
{
    use Factories;

    public function testCreateInvoice(): void
    {
        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'invoice' => (new Invoice())->setInvoiceDate(new DateTimeImmutable('2021-01-01')),
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @throws MathException
     */
    public function testCreateInvoiceWithMultipleLines(): void
    {
        $invoice = (new Invoice())->setInvoiceDate(new DateTimeImmutable('2021-01-01'));
        $invoice->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();
        $invoice->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'invoice' => $invoice,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @throws MathException
     */
    public function testCreateInvoiceWithTaxRates(): void
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

        $invoice = (new Invoice())->setInvoiceDate(new DateTimeImmutable('2021-01-01'));
        $invoice->addLine((new Line())->setPrice(10000)->setQty(1))->updateLines();

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'invoice' => $invoice,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }
}
