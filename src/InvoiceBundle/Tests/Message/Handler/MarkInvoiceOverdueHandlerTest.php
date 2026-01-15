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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
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

    public function testHandlerMarksInvoiceOverdue(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne([
            'status' => Graph::STATUS_PENDING,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        /** @var WorkflowInterface&MockObject $invoiceStateMachine */
        $invoiceStateMachine = $this->createMock(WorkflowInterface::class);
        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);

        $invoiceStateMachine->expects($this->once())
            ->method('can')
            ->with($this->callback(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->willReturn(true);

        $invoiceStateMachine->expects($this->once())
            ->method('apply')
            ->with($this->callback(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE);

        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);

        $registry->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(fn ($inv) => $inv->getId()->equals($invoice->getId())));
        $em->expects($this->once())
            ->method('flush');

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
            'status' => Graph::STATUS_PAID,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        $transitionService = new InvoiceStatusTransitionService(
            $this->createMock(WorkflowInterface::class),
            $this->createMock(ManagerRegistry::class),
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
            $this->createMock(WorkflowInterface::class),
            $this->createMock(ManagerRegistry::class),
        );

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Invoice not found for overdue processing', $this->anything());

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
            'status' => Graph::STATUS_PENDING,
            'due' => new DateTimeImmutable('yesterday'),
            'company' => $company,
        ]);

        /** @var WorkflowInterface&MockObject $invoiceStateMachine */
        $invoiceStateMachine = $this->createMock(WorkflowInterface::class);
        $transitionService = new InvoiceStatusTransitionService(
            $invoiceStateMachine,
            $this->createMock(ManagerRegistry::class),
        );

        $invoiceStateMachine->expects($this->once())
            ->method('can')
            ->with($this->callback(fn ($inv) => $inv->getId()->equals($invoice->getId())), Graph::TRANSITION_OVERDUE)
            ->willReturn(false);

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Invalid transition when marking invoice overdue', $this->anything());

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
