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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Listener\FreezeCurrencyListener;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Regression pin: {@see FreezeCurrencyListener} is not subscribed to
 * `workflow.recurring_invoice.transition`, so a recurring template's `currency_code`
 * must stay null across every transition in its own workflow, including a return to
 * Draft. It always resolves its currency through {@see RecurringInvoice::getClient()}.
 */
#[CoversClass(FreezeCurrencyListener::class)]
final class RecurringInvoiceCurrencyFreezeTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testRecurringInvoiceCurrencyCodeStaysNullAcrossActivateAndEdit(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);
        $recurringInvoiceId = RecurringInvoiceFactory::createOne([
            'status' => RecurringInvoiceStatus::Draft,
            'client' => $client,
            'company' => $this->company,
        ])->getId();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $repository = $container->get(RecurringInvoiceRepository::class);
        $recurringInvoice = $repository->find($recurringInvoiceId);
        self::assertInstanceOf(RecurringInvoice::class, $recurringInvoice);

        $workflow = $container->get('state_machine.recurring_invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        $workflow->apply($recurringInvoice, Graph::TRANSITION_ACTIVATE);
        $entityManager->flush();
        self::assertNull($recurringInvoice->getCurrencyCode());

        $workflow->apply($recurringInvoice, 'edit');
        $entityManager->flush();
        self::assertNull($recurringInvoice->getCurrencyCode());

        self::assertSame('USD', $recurringInvoice->getCurrency()->getCode());
    }
}
