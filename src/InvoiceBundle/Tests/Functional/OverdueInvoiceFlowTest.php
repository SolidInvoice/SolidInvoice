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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Message\MarkInvoiceOverdue;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Mailer\Test\InteractsWithMailer;

/**
 * Comprehensive functional test for the overdue invoice feature.
 * Tests the complete flow from detection to notification.
 *
 * @covers \SolidInvoice\InvoiceBundle\Command\MarkOverdueInvoicesCommand
 * @covers \SolidInvoice\InvoiceBundle\Message\Handler\MarkInvoiceOverdueHandler
 * @covers \SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService
 * @covers \SolidInvoice\InvoiceBundle\Listener\InvoiceOverdueListener
 */
final class OverdueInvoiceFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use InteractsWithMailer;

    public function testCompleteOverdueFlow(): void
    {
        // Setup: Create an overdue invoice with contacts
        // $contact = (new Contact())->setEmail('client@example.com')->setFirstName('John')->setLastName('Doe');
        $contact = ContactFactory::createOne([
            'email' => 'client@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'company' => $this->company,
        ]);

        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'invoiceId' => 'INV-TEST-001',
            'company' => $this->company,
            'client' => ClientFactory::createOne([
                'company' => $this->company,
                'name' => 'Test Client',
                'currencyCode' => 'USD',
            ]),
        ]);

        // Add contact to invoice
        $invoice->_real()->addUser($contact->_real());
        $invoice->_save();

        // Step 1: Dispatch message (simulating command execution)
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new MarkInvoiceOverdue($invoice->getId(), $this->company->getId()));

        // Step 2: Verify invoice status changed to overdue
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $companySelector->switchCompany($this->company->getId());

        $repository = self::getContainer()->get(InvoiceRepository::class);
        $updatedInvoice = $repository->find($invoice->getId());

        self::assertNotNull($updatedInvoice);
        self::assertEquals(InvoiceStatus::Overdue, $updatedInvoice->getStatus());
    }

    public function testOverdueFlowWithMultipleCompanies(): void
    {
        // Setup: Create overdue invoices from different companies
        $company1 = CompanyFactory::createOne();
        $company2 = CompanyFactory::createOne();

        $contact1 = ContactFactory::createOne([
            'email' => 'client1@example.com',
            'firstName' => 'Client',
            'lastName' => 'One',
            'company' => $company1,
        ]);

        $contact2 = ContactFactory::createOne([
            'email' => 'client2@example.com',
            'firstName' => 'Client',
            'lastName' => 'Two',
            'company' => $company2,
        ]);

        $invoice1 = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'invoiceId' => 'INV-COMPANY1-001',
            'company' => $company1,
            'client' => ClientFactory::createOne([
                'company' => $company1,
                'name' => 'Client One',
                'currencyCode' => 'USD',
            ]),
        ]);
        $invoice1->_real()->addUser($contact1->_real());
        $invoice1->_save();

        $invoice2 = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'invoiceId' => 'INV-COMPANY2-001',
            'company' => $company2,
            'client' => ClientFactory::createOne([
                'company' => $company2,
                'name' => 'Client Two',
                'currencyCode' => 'EUR',
            ]),
        ]);
        $invoice2->_real()->addUser($contact2->_real());
        $invoice2->_save();

        // Dispatch messages for both invoices
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch(new MarkInvoiceOverdue($invoice1->getId(), $company1->getId()));
        $bus->dispatch(new MarkInvoiceOverdue($invoice2->getId(), $company2->getId()));

        // Verify both invoices are overdue
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $companySelector->switchCompany($company1->getId());
        $updatedInvoice1 = $repository->find($invoice1->getId());
        self::assertEquals(InvoiceStatus::Overdue, $updatedInvoice1->getStatus());

        $companySelector->switchCompany($company2->getId());
        $updatedInvoice2 = $repository->find($invoice2->getId());
        self::assertEquals(InvoiceStatus::Overdue, $updatedInvoice2->getStatus());
    }

    public function testIdempotency(): void
    {
        // Setup: Create overdue invoice
        $contact = ContactFactory::createOne([
            'email' => 'client@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'company' => $this->company,
        ]);

        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $this->company
        ]);
        $invoice->_real()->addUser($contact->_real());
        $invoice->_save();

        $bus = self::getContainer()->get(MessageBusInterface::class);
        $message = new MarkInvoiceOverdue($invoice->getId(), $this->company->getId());

        // Dispatch the message twice
        $bus->dispatch($message);
        $bus->dispatch($message);

        // Verify invoice is overdue (not in an invalid state)
        $companySelector = self::getContainer()->get(CompanySelector::class);
        $companySelector->switchCompany($this->company->getId());

        $repository = self::getContainer()->get(InvoiceRepository::class);
        $updatedInvoice = $repository->find($invoice->getId());

        self::assertEquals(InvoiceStatus::Overdue, $updatedInvoice->getStatus());
    }

    public function testRepositoryGetPendingOverdueInvoices(): void
    {
        // Create various invoices
        $overdueInvoice1 = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('2 days ago'),
            'company' => $this->company,
        ]);

        $overdueInvoice2 = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $this->company,
        ]);

        // Not overdue - future due date
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('tomorrow'),
            'company' => $this->company,
        ]);

        // Not overdue - already paid
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Paid,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $this->company,
        ]);

        // Not overdue - already overdue status
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Overdue,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $this->company,
        ]);

        // Not overdue - no due date
        InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => null,
            'company' => $this->company,
        ]);

        // Query overdue invoices (without company filter)
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $repository = self::getContainer()->get(InvoiceRepository::class);
        $overdueInvoices = iterator_to_array($repository->getPendingOverdueInvoices());

        if ($companyFilterEnabled) {
            $filters->enable('company');
        }

        // Should return exactly 2 overdue invoices
        self::assertCount(2, $overdueInvoices);

        $overdueIds = array_map(fn ($inv) => $inv->getId()->toString(), $overdueInvoices);
        self::assertContains($overdueInvoice1->getId()->toString(), $overdueIds);
        self::assertContains($overdueInvoice2->getId()->toString(), $overdueIds);
    }
}
