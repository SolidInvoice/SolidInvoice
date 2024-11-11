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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\FilterCollection;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\StateMachine;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Message\Handler\CreateInvoiceFromRecurringHandler */
final class CreateInvoiceFromRecurringHandlerTest extends KernelTestCase
{
    use Factories;

    public function testHandler(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setCompany(CompanyFactory::createOne()->_real());
        $invoice = new Invoice();
        $configuration = new Configuration();

        $invoiceManager = $this->createMock(InvoiceManager::class);
        $invoiceStateMachine = $this->createMock(StateMachine::class);
        $registry = $this->createMock(Registry::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $configuration->addFilter('company', CompanyFilter::class);

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

        $registry->expects(self::once())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager->expects(self::once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $filters = new FilterCollection($entityManager);

        $entityManager->expects(self::exactly(2))
            ->method('getFilters')
            ->willReturn($filters);

        $handler = new CreateInvoiceFromRecurringHandler($invoiceManager, $invoiceStateMachine, new CompanySelector($registry), new NullLogger());
        $handler(new CreateInvoiceFromRecurring($recurringInvoice));
    }
}
