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
use SolidInvoice\InvoiceBundle\Action\Transition;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

final class TransitionTest extends TestCase
{
    public function testCancelFlashesInfoNotSuccess(): void
    {
        $invoice = new Invoice();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($invoice, Graph::TRANSITION_CANCEL)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($invoice, Graph::TRANSITION_CANCEL)
            ->willReturn(new Marking([InvoiceStatus::Cancelled->value => 1]));

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/view/123');

        $action = new Transition($router, $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $response = $action(new Request(), Graph::TRANSITION_CANCEL, $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        self::assertSame(['info' => 'invoice.transition.action.cancel'], $flashes);
    }

    public function testArchiveFlashesSuccess(): void
    {
        $invoice = new Invoice();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($invoice, Graph::TRANSITION_ARCHIVE)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($invoice, Graph::TRANSITION_ARCHIVE)
            ->willReturn(new Marking([InvoiceStatus::Archived->value => 1]));

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices');

        $action = new Transition($router, $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $response = $action(new Request(), Graph::TRANSITION_ARCHIVE, $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        $flashes = iterator_to_array($response->getFlash());
        self::assertSame(['success' => 'invoice.transition.action.archive'], $flashes);
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
