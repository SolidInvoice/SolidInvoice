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

namespace SolidInvoice\InvoiceBundle\Tests\Message\Handler;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\StateMachine;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler */
final class CreateInvoiceFromRecurringHandlerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testHandler(): void
    {
        $recurringInvoice = $this->createMock(RecurringInvoice::class);
        $company = CompanyFactory::createOne()->_real();
        $recurringInvoiceId = new Ulid();
        $invoice = new Invoice();
        $configuration = new Configuration();

        $invoiceManager = $this->createMock(InvoiceManager::class);
        $invoiceStateMachine = $this->createMock(StateMachine::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(Registry::class);
        $recurringInvoiceRepository = $this->createMock(RecurringInvoiceRepository::class);
        $clock = $this->createMock(ClockInterface::class);

        $entityManager->expects($this->once())->method('getConnection')->willReturn(new Connection([], new Driver()));

        $configuration->addFilter('company', CompanyFilter::class);

        $recurringInvoiceRepository->expects(self::once())
            ->method('find')
            ->with($recurringInvoiceId)
            ->willReturn($recurringInvoice);

        $recurringInvoice->expects(self::once())
            ->method('getCompany')
            ->willReturn($company);

        $clock->expects(self::once())
            ->method('now')
            ->willReturn(new DateTimeImmutable('2024-01-15'));

        $recurringInvoice->expects(self::once())
            ->method('hasInvoiceForDay')
            ->with(self::isInstanceOf(DateTimeImmutable::class))
            ->willReturn(false);

        $invoiceManager->expects(self::once())
            ->method('createFromRecurring')
            ->with($recurringInvoice)
            ->willReturn($invoice);

        $invoiceManager->expects(self::once())
            ->method('create')
            ->with($invoice);

        $invoiceStateMachine->expects(self::once())
            ->method('apply')
            ->with($invoice, Graph::TRANSITION_ACCEPT);

        $registry->expects(self::atLeastOnce())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $filters = new FilterCollection($entityManager);

        $entityManager->expects(self::atLeastOnce())
            ->method('getFilters')
            ->willReturn($filters);

        $companySelector = new CompanySelector($registry);

        $handler = new CreateInvoiceFromRecurringHandler(
            $invoiceManager,
            $invoiceStateMachine,
            $companySelector,
            new NullLogger(),
            $clock,
            $recurringInvoiceRepository
        );
        $handler(new CreateInvoiceFromRecurring($recurringInvoiceId));
    }

    public function testHandlerSkipsWhenInvoiceAlreadyExistsForDay(): void
    {
        $recurringInvoice = $this->createMock(RecurringInvoice::class);
        $company = CompanyFactory::createOne()->_real();
        $recurringInvoiceId = new Ulid();
        $configuration = new Configuration();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $registry = $this->createMock(Registry::class);
        $recurringInvoiceRepository = $this->createMock(RecurringInvoiceRepository::class);
        $clock = $this->createMock(ClockInterface::class);
        $invoiceManager = $this->createMock(InvoiceManager::class);
        $invoiceStateMachine = $this->createMock(StateMachine::class);

        $entityManager->expects($this->once())->method('getConnection')->willReturn(new Connection([], new Driver()));
        $configuration->addFilter('company', CompanyFilter::class);

        $recurringInvoiceRepository->expects(self::once())
            ->method('find')
            ->with($recurringInvoiceId)
            ->willReturn($recurringInvoice);

        $recurringInvoice->expects(self::once())
            ->method('getCompany')
            ->willReturn($company);

        $clock->expects(self::once())
            ->method('now')
            ->willReturn(new DateTimeImmutable('2024-01-15'));

        $recurringInvoice->expects(self::once())
            ->method('hasInvoiceForDay')
            ->with(self::isInstanceOf(DateTimeImmutable::class))
            ->willReturn(true);

        // Should not call these methods if invoice already exists
        $invoiceManager->expects(self::never())
            ->method('createFromRecurring');

        $invoiceManager->expects(self::never())
            ->method('create');

        $invoiceStateMachine->expects(self::never())
            ->method('apply');

        $registry->expects(self::atLeastOnce())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $filters = new FilterCollection($entityManager);

        $entityManager->expects(self::atLeastOnce())
            ->method('getFilters')
            ->willReturn($filters);

        $companySelector = new CompanySelector($registry);

        $handler = new CreateInvoiceFromRecurringHandler(
            $invoiceManager,
            $invoiceStateMachine,
            $companySelector,
            new NullLogger(),
            $clock,
            $recurringInvoiceRepository
        );
        $handler(new CreateInvoiceFromRecurring($recurringInvoiceId));
    }

    public function testHandlerLogsErrorWhenRecurringInvoiceNotFound(): void
    {
        $recurringInvoiceId = new Ulid();
        $logger = $this->createMock(LoggerInterface::class);

        $recurringInvoiceRepository = $this->createMock(RecurringInvoiceRepository::class);
        $clock = $this->createMock(ClockInterface::class);
        $invoiceManager = $this->createMock(InvoiceManager::class);
        $invoiceStateMachine = $this->createMock(StateMachine::class);
        $registry = $this->createMock(Registry::class);

        $recurringInvoiceRepository->expects(self::once())
            ->method('find')
            ->with($recurringInvoiceId)
            ->willReturn(null);

        $logger->expects(self::once())
            ->method('error')
            ->with('Recurring invoice not found', ['recurring_invoice_id' => $recurringInvoiceId]);

        // Should not call these methods if recurring invoice not found
        $invoiceManager->expects(self::never())
            ->method('createFromRecurring');

        $invoiceManager->expects(self::never())
            ->method('create');

        $invoiceStateMachine->expects(self::never())
            ->method('apply');

        $companySelector = new CompanySelector($registry);

        $handler = new CreateInvoiceFromRecurringHandler(
            $invoiceManager,
            $invoiceStateMachine,
            $companySelector,
            $logger,
            $clock,
            $recurringInvoiceRepository
        );
        $handler(new CreateInvoiceFromRecurring($recurringInvoiceId));
    }
}
