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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Message\Handler\MarkInvoiceOverdueHandler;
use SolidInvoice\InvoiceBundle\Message\MarkInvoiceOverdue;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Message\Handler\MarkInvoiceOverdueHandler */
final class MarkInvoiceOverdueHandlerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use MockeryPHPUnitIntegration;

    public function testHandlerMarksInvoiceOverdue(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        $invoiceStateMachine = M::mock(WorkflowInterface::class);
        $registry = M::mock(ManagerRegistry::class);

        $invoiceStateMachine->shouldReceive('can')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once()
            ->andReturn(true);

        $invoiceStateMachine->shouldReceive('apply')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once();

        $em = M::mock(EntityManagerInterface::class);

        $registry->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $em->shouldReceive('persist')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())))
            ->once();
        $em->shouldReceive('flush')
            ->once();

        $transitionService = new InvoiceStatusTransitionService(
            $invoiceStateMachine,
            $registry,
        );

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            new NullLogger()
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);
    }

    public function testHandlerSkipsNonPendingInvoice(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Paid,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        $transitionService = new InvoiceStatusTransitionService(
            M::mock(WorkflowInterface::class),
            M::mock(ManagerRegistry::class),
        );

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger = new BufferingLogger()
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);

        self::assertSame([
            [
                'info',
                'Invoice no longer pending, skipping overdue processing',
                [
                    'invoice_id' => $invoice->getId()->toString(),
                    'current_status' => 'paid',
                ],
            ],
        ], $logger->cleanLogs());
    }

    public function testHandlerLogsWarningWhenInvoiceNotFound(): void
    {
        $company = CompanyFactory::createOne();
        $nonExistentId = new Ulid();

        $transitionService = new InvoiceStatusTransitionService(
            M::mock(WorkflowInterface::class),
            M::mock(ManagerRegistry::class),
        );

        $logger = M::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Invoice not found for overdue processing', M::any());

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger
        );

        $message = new MarkInvoiceOverdue($nonExistentId, $company->getId());
        $handler($message);
    }

    public function testHandlerLogsErrorOnInvalidTransition(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => InvoiceStatus::Pending,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        $invoiceStateMachine = M::mock(WorkflowInterface::class);
        $transitionService = new InvoiceStatusTransitionService(
            $invoiceStateMachine,
            M::mock(ManagerRegistry::class),
        );

        $invoiceStateMachine->shouldReceive('can')
            ->with(M::on(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->once()
            ->andReturn(false);

        $logger = M::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Invalid transition when marking invoice overdue', M::any());

        $companySelector = self::getContainer()->get(CompanySelector::class);
        $repository = self::getContainer()->get(InvoiceRepository::class);

        $handler = new MarkInvoiceOverdueHandler(
            $repository,
            $transitionService,
            $companySelector,
            $logger
        );

        $message = new MarkInvoiceOverdue($invoice->getId(), $company->getId());
        $handler($message);
    }
}
