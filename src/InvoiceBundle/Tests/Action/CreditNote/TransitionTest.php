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

namespace SolidInvoice\InvoiceBundle\Tests\Action\CreditNote;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Transition;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(Transition::class)]
final class TransitionTest extends TestCase
{
    public function testIssueFlashesSuccess(): void
    {
        $creditNote = new CreditNote();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($creditNote, Graph::TRANSITION_ISSUE)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($creditNote, Graph::TRANSITION_ISSUE)
            ->willReturn(new Marking());

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = new Transition($router, $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $session = new Session(new MockArraySessionStorage());
        $response = $action(Graph::TRANSITION_ISSUE, $creditNote, $session);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(['credit_note.transition.action.issue'], $session->getFlashBag()->get('success'));
    }

    public function testDisallowedTransitionThrows(): void
    {
        $creditNote = new CreditNote();

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($creditNote, Graph::TRANSITION_ISSUE)->willReturn(false);
        $workflow->expects($this->never())->method('apply');

        $action = new Transition($this->createStub(RouterInterface::class), $workflow);
        $action->setDoctrine($this->createDoctrineStub());

        $this->expectException(InvalidTransitionException::class);

        $action(Graph::TRANSITION_ISSUE, $creditNote, new Session(new MockArraySessionStorage()));
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
