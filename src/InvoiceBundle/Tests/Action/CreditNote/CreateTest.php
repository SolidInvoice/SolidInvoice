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
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Create;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[CoversClass(Create::class)]
final class CreateTest extends TestCase
{
    public function testDraftDoesNotApplyTheIssueTransition(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('apply');

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = $this->buildAction($workflow, $router, $this->buildDoctrineWithManager(), $this->buildSubmittedForm());
        $response = $action($this->buildRequest('draft'));

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testIssueAppliesTheIssueTransitionWhenAllowed(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())->method('can')->with($this->isInstanceOf(CreditNote::class), Graph::TRANSITION_ISSUE)->willReturn(true);
        $workflow->expects($this->once())->method('apply')->with($this->isInstanceOf(CreditNote::class), Graph::TRANSITION_ISSUE);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/credit-notes/123');

        $action = $this->buildAction($workflow, $router, $this->buildDoctrineWithManager(), $this->buildSubmittedForm());
        $response = $action($this->buildRequest('issue'));

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    private function buildAction(WorkflowInterface $workflow, RouterInterface $router, ManagerRegistry $doctrine, FormInterface $form): Create
    {
        $clientRepository = $this->createStub(ClientRepository::class);
        $clientRepository->method('getTotalClients')->willReturn(0);

        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        return new Create(
            $formFactory,
            $clientRepository,
            $workflow,
            $router,
            $doctrine,
            $this->createStub(TotalCalculator::class),
        );
    }

    /**
     * @return FormInterface<mixed>
     */
    private function buildSubmittedForm(): FormInterface
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('handleRequest');

        return $form;
    }

    private function buildRequest(string $saveAction): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/', Request::METHOD_POST, ['save' => $saveAction]);
        $request->setSession($session);

        return $request;
    }

    private function buildDoctrineWithManager(): ManagerRegistry
    {
        $em = $this->createStub(ObjectManager::class);
        $em->method('persist');
        $em->method('flush');

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        return $doctrine;
    }
}
