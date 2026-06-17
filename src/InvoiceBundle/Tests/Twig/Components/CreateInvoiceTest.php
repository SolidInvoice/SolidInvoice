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
use Carbon\CarbonImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Manager\InvoiceFormManager;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Twig\Components\CreateInvoice;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

final class CreateInvoiceTest extends LiveComponentTest
{
    use Factories;

    public function testCreateInvoice(): void
    {
        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');

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
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

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

        $tax = new Tax()
            ->setName('VAT')
            ->setRate(20)
            ->setType(Tax::TYPE_INCLUSIVE);

        $em->persist($tax);

        $em->flush();

        $dto = new InvoiceFormDTO();
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

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
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
        $dto->client = $client->_real();
        $dto->lines->add(new Line()->setPrice(10000)->setQty(1));

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
     * Regression test for issue #2347.
     *
     * When editing a Pending invoice (accept transition already applied) and clicking
     * "Save & Send", the component must not throw NotEnabledTransitionException.
     * The workflow guard introduced in CreateInvoice::saveInvoice() skips the
     * accept transition when it is no longer enabled.
     */
    public function testSaveSendDoesNotThrowWhenInvoiceAlreadyPending(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();

        $client = ClientFactory::createOne([
            'name' => 'Acme Corp',
            'currencyCode' => 'USD',
        ])->_real();

        $contact = ContactFactory::createOne([
            'firstName' => 'Alice',
            'lastName' => 'Smith',
            'email' => 'alice@example.com',
            'client' => $client,
        ])->_real();

        // Build a persisted Pending invoice (accept transition already applied).
        $line = new Line()
            ->setDescription('Consulting')
            ->setPrice(10000)
            ->setQty(1.0);

        $invoice = new Invoice();
        $invoice->setStatus(InvoiceStatus::Pending);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-TEST-001');
        $invoice->setInvoiceDate(CarbonImmutable::parse('2024-01-15'));
        $invoice->addUser($contact);
        $invoice->addLine($line);

        $em->persist($invoice);
        $em->flush();

        // Verify the accept transition is indeed unavailable for a Pending invoice.
        /** @var WorkflowInterface $stateMachine */
        $stateMachine = self::getContainer()->get('state_machine.invoice');
        self::assertFalse(
            $stateMachine->can($invoice, Graph::TRANSITION_ACCEPT),
            'The accept transition must not be available for a Pending invoice.',
        );

        // Build the DTO from the existing invoice (simulates the edit page load).
        /** @var InvoiceFormManager $formManager */
        $formManager = self::getContainer()->get(InvoiceFormManager::class);
        $dto = $formManager->createDTOFromInvoice($invoice);

        $component = $this->createLiveComponent(
            name: CreateInvoice::class,
            data: [
                'dto' => $dto,
                'isEdit' => true,
                'invoice' => $invoice,
            ],
            client: $this->client,
        )->actingAs($this->getUser());

        // Before the fix this threw NotEnabledTransitionException.
        $component->call('saveSend');

        // The invoice status must remain Pending — no re-accept attempted.
        $em = self::getContainer()->get('doctrine')->getManager();
        $refreshedInvoice = $em->find(Invoice::class, $invoice->getId());
        self::assertNotNull($refreshedInvoice);
        self::assertSame(InvoiceStatus::Pending, $refreshedInvoice->getStatus());

        // The action must have produced a redirect (not a 422 / exception page).
        $response = $this->client->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
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
        $dto->invoiceDate = CarbonImmutable::parse('2021-01-01');
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
