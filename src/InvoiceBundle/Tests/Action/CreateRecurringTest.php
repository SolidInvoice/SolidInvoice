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

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Action\CreateRecurring;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

final class CreateRecurringTest extends TestCase
{
    /**
     * @param FormInterface<mixed> $form
     */
    private function buildAction(WorkflowInterface $workflow, RouterInterface $router, ManagerRegistry $doctrine, FormInterface $form): CreateRecurring
    {
        $clientRepository = $this->createStub(ClientRepository::class);
        $clientRepository->method('getTotalClients')->willReturn(2);

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);
        $featureGate->method('canUse')->willReturn(true);

        $invoiceRepository = $this->createStub(InvoiceRepository::class);
        $invoiceRepository->method('countCreatedInMonth')->willReturn(0);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(CarbonImmutable::now());

        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('form.factory')
            ->willReturn($formFactory);
        $container->method('has')->willReturn(true);

        $action = new CreateRecurring(
            $clientRepository,
            $workflow,
            $router,
            $doctrine,
            $this->createStub(TotalCalculator::class),
            $featureGate,
            $invoiceRepository,
            $clock,
        );
        $action->setContainer($container);

        return $action;
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

    public function testPublishDoesNotActivateWhenTransitionNotEnabled(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('apply')
            ->with($this->anything(), Graph::TRANSITION_NEW);
        $workflow->expects($this->once())
            ->method('can')
            ->with($this->anything(), Graph::TRANSITION_ACTIVATE)
            ->willReturn(false);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/view/123');

        $action = $this->buildAction($workflow, $router, $this->buildDoctrineWithManager(), $this->buildSubmittedForm());
        $response = $action($this->buildRequest('publish'));

        self::assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPublishActivatesWhenTransitionIsEnabled(): void
    {
        $appliedTransitions = [];

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->exactly(2))
            ->method('apply')
            ->willReturnCallback(static function (object $subject, string $transition) use (&$appliedTransitions): Marking {
                $appliedTransitions[] = $transition;

                return new Marking();
            });
        $workflow->expects($this->once())
            ->method('can')
            ->with($this->anything(), Graph::TRANSITION_ACTIVATE)
            ->willReturn(true);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/invoices/recurring/view/123');

        $action = $this->buildAction($workflow, $router, $this->buildDoctrineWithManager(), $this->buildSubmittedForm());
        $response = $action($this->buildRequest('publish'));

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame([Graph::TRANSITION_NEW, Graph::TRANSITION_ACTIVATE], $appliedTransitions);
    }
}
