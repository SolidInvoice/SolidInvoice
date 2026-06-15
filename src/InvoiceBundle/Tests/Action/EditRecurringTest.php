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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Action\EditRecurring;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class EditRecurringTest extends TestCase
{
    private function buildAction(
        FormFactoryInterface $formFactory,
        RouterInterface $router,
        WorkflowInterface $workflow,
        ManagerRegistry $doctrine,
    ): EditRecurring {
        $featureGate = $this->createMock(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        return new EditRecurring($formFactory, $router, $workflow, $doctrine, $this->createStub(TotalCalculator::class), $featureGate);
    }

    private function buildInvoice(RecurringInvoiceStatus $status): RecurringInvoice
    {
        $client = new Client();
        $client->setCurrencyCode('USD');

        $invoice = new RecurringInvoice();
        $invoice->setStatus($status);
        $invoice->setClient($client);

        return $invoice;
    }

    /**
     * @return FormInterface<mixed>
     */
    private function buildSubmittedForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
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

    public function testPublishDoesNotActivateWhenTransitionNotEnabled(): void
    {
        $invoice = $this->buildInvoice(RecurringInvoiceStatus::Active);

        $form = $this->buildSubmittedForm();

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_ACTIVATE)
            ->willReturn(false);
        $workflow->expects($this->never())
            ->method('apply');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/view/123');

        $action = $this->buildAction($formFactory, $router, $workflow, $doctrine);
        $response = $action($this->buildRequest('publish'), $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPublishActivatesWhenTransitionIsEnabled(): void
    {
        $invoice = $this->buildInvoice(RecurringInvoiceStatus::Draft);

        $form = $this->buildSubmittedForm();

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_ACTIVATE)
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('apply')
            ->with($invoice, Graph::TRANSITION_ACTIVATE);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/view/123');

        $action = $this->buildAction($formFactory, $router, $workflow, $doctrine);
        $response = $action($this->buildRequest('publish'), $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testSaveWithoutPublishSkipsWorkflow(): void
    {
        $invoice = $this->buildInvoice(RecurringInvoiceStatus::Draft);

        $form = $this->buildSubmittedForm();

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('can');
        $workflow->expects($this->never())->method('apply');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/view/123');

        $action = $this->buildAction($formFactory, $router, $workflow, $doctrine);
        $response = $action($this->buildRequest('draft'), $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
    }
}
