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

namespace SolidInvoice\InvoiceBundle\Tests\Action;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Action\RecurringTransition;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class RecurringTransitionTest extends TestCase
{
    public function testCancelFlashesInfoUnderTheRecurringCatalogKey(): void
    {
        $invoice = new RecurringInvoice();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($invoice, Graph::TRANSITION_CANCEL)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($invoice, Graph::TRANSITION_CANCEL);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/view-recurring/123');

        $action = new RecurringTransition($router, $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $response = $action(new Request(), Graph::TRANSITION_CANCEL, $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        self::assertSame(['info' => 'invoice.transition.recurring.action.cancel'], $flashes);
    }

    public function testActivateFlashesSuccessUnderTheRecurringCatalogKey(): void
    {
        $invoice = new RecurringInvoice();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($invoice, Graph::TRANSITION_ACTIVATE)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($invoice, Graph::TRANSITION_ACTIVATE);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/view-recurring/123');

        $action = new RecurringTransition($router, $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $response = $action(new Request(), Graph::TRANSITION_ACTIVATE, $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        // Must say "Recurring invoice", never "Invoice" — the invoice state machine
        // has no `activate` transition, so this key is reached only from here.
        self::assertSame(['success' => 'invoice.transition.recurring.action.activate'], $flashes);
    }

    private function createDoctrineStub(): ManagerRegistry
    {
        $em = $this->createStub(ObjectManager::class);
        $em->method('persist');
        $em->method('flush');

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        return $doctrine;
    }
}
