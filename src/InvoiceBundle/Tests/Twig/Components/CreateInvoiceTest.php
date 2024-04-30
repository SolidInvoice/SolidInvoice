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
use Ramsey\Uuid\Uuid;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice;
use SolidInvoice\TaxBundle\Entity\Tax;
use Zenstruck\Foundry\Test\Factories;

final class CreateInvoiceTest extends LiveComponentTest
{
    use Factories;

    public function testCreateInvoice(): void
    {
        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'invoice' => new Invoice(),
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }

    /**
     * @throws MathException
     */
    public function testCreateInvoiceWithMultipleLines(): void
    {
        $invoice = new Invoice();
        $invoice->addItem((new Item())->setPrice(10000)->setQty(1))->updateItems();
        $invoice->addItem((new Item())->setPrice(10000)->setQty(1))->updateItems();

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
            $this->id = Uuid::fromString('0f9e91e6-06ba-11ef-a331-5a2cf21a5680'); // @phpstan-ignore-line
        })(...)->call($tax);

        $em->flush();

        $invoice = new Invoice();
        $invoice->addItem((new Item())->setPrice(10000)->setQty(1))->updateItems();

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'invoice' => $invoice,
            ]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($component->render()->toString());
    }
}
