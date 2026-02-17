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

namespace SolidInvoice\InvoiceBundle\Tests\Twig\Components;

use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
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
        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = new DateTimeImmutable('2021-01-01');

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    /**
     * @throws MathException
     */
    public function testCreateInvoiceWithMultipleLines(): void
    {
        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = new DateTimeImmutable('2021-01-01');
        $dto->lines->add((new Line())->setPrice(10000)->setQty(1));
        $dto->lines->add((new Line())->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
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

        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = new DateTimeImmutable('2021-01-01');
        $dto->lines->add((new Line())->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($component->render()->toString()));
    }

    /**
     * Tests that contacts are auto-selected when a client is pre-selected.
     * The component's PostMount hook should auto-select all contacts.
     *
     * @throws MathException
     */
    public function testCreateInvoiceWithPreselectedClientAutoSelectsContacts(): void
    {
        $client = ClientFactory::createOne([
            'name' => 'Test Client',
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'client' => $client,
        ]);

        ContactFactory::createOne([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'client' => $client,
        ]);

        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = new DateTimeImmutable('2021-01-01');
        $dto->client = $client->_real();
        $dto->lines->add((new Line())->setPrice(10000)->setQty(1));

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        $rendered = $component->render()->toString();

        // Verify both contacts are displayed
        self::assertStringContainsString('John Doe', $rendered);
        self::assertStringContainsString('Jane Smith', $rendered);

        // Verify checkboxes are checked (contacts are selected by PostMount hook)
        self::assertStringContainsString('checked', $rendered);

        $this->assertMatchesHtmlSnapshot($this->replaceChecksum($this->replaceUuid($rendered)));
    }

    /**
     * Tests that the component correctly tracks previous client ID.
     * The PostMount hook should set previousClientId when auto-selecting contacts.
     */
    public function testPreviousClientIdIsTracked(): void
    {
        $client = ClientFactory::createOne([
            'name' => 'Test Client',
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'client' => $client,
        ]);

        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = new DateTimeImmutable('2021-01-01');
        $dto->client = $client->_real();

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: ['dto' => $dto]
        )->actingAs($this->getUser());

        // Render the component
        $component->render();

        // Access the component instance to verify previousClientId is set
        $componentInstance = $component->component();

        self::assertInstanceOf(CreateInvoice::class, $componentInstance);
        self::assertSame((string) $client->getId(), $componentInstance->previousClientId);
    }
}
