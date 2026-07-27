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

namespace SolidInvoice\PaymentBundle\Tests\Action;

use Brick\Math\BigNumber;
use Payum\Core\Registry\RegistryInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Action\Prepare;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

final class PrepareTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private function buildAction(
        PaymentMethodRepository $paymentMethodRepository,
        InvoiceRepository $invoiceRepository,
        WorkflowInterface $invoiceStateMachine,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        ?ModeResolver $modeResolver = null,
        ?FormFactoryInterface $formFactory = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): Prepare {
        $action = new Prepare(
            $invoiceStateMachine,
            $paymentMethodRepository,
            $authorizationChecker,
            self::getContainer()->get(TokenStorageInterface::class),
            $formFactory ?? self::getContainer()->get(FormFactoryInterface::class),
            $eventDispatcher ?? self::getContainer()->get(EventDispatcherInterface::class),
            self::getContainer()->get(RegistryInterface::class),
            $router,
            self::getContainer()->get(CompanySelector::class),
            $invoiceRepository,
            $modeResolver ?? new ModeResolver(),
        );

        $action->setDoctrine(self::getContainer()->get('doctrine'));

        return $action;
    }

    /**
     * @param array<string, mixed> $data
     * @return FormInterface<mixed>
     */
    private function buildSubmittedForm(array $data): FormInterface
    {
        $form = $this->createStub(FormInterface::class);
        $form->method('handleRequest');
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('getData')->willReturn($data);

        return $form;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function buildFormFactory(FormInterface $form): FormFactoryInterface
    {
        $formFactory = $this->createStub(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);

        return $formFactory;
    }

    public function testNoPaymentMethodsAvailableRedirectsAuthenticatedUserWithFlash(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ])->_real();

        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($invoice);

        $paymentMethodRepository = $this->createMock(PaymentMethodRepository::class);
        $paymentMethodRepository->expects(self::once())
            ->method('getTotalMethodsConfigured')
            ->willReturn(0);

        $invoiceStateMachine = $this->createMock(WorkflowInterface::class);
        $invoiceStateMachine->expects(self::once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_PAY)
            ->willReturn(true);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->willReturn(true);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
        );

        $request = Request::create('/pay/' . (string) $invoice->getUuid());
        $response = $action($request, (string) $invoice->getUuid());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_DANGER, $flashes);
        self::assertSame('payment.create.exception.no_payment_methods', $flashes[FlashResponse::FLASH_DANGER]);
    }

    public function testNoPaymentMethodsAvailableRedirectsUnauthenticatedUserToExternalView(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ])->_real();

        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($invoice);

        $paymentMethodRepository = $this->createMock(PaymentMethodRepository::class);
        $paymentMethodRepository->expects(self::once())
            ->method('getTotalMethodsConfigured')
            ->willReturn(0);

        $invoiceStateMachine = $this->createMock(WorkflowInterface::class);
        $invoiceStateMachine->expects(self::once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_PAY)
            ->willReturn(true);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->willReturn(false);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_view_invoice_external', self::anything())
            ->willReturn('/view/invoice/abc-123');

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
        );

        $request = Request::create('/pay/' . (string) $invoice->getUuid());
        $response = $action($request, (string) $invoice->getUuid());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/view/invoice/abc-123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_DANGER, $flashes);
        self::assertSame('payment.create.exception.no_payment_methods', $flashes[FlashResponse::FLASH_DANGER]);
    }

    public function testInvoiceNotFoundThrowsNotFoundException(): void
    {
        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $paymentMethodRepository = $this->createStub(PaymentMethodRepository::class);
        $invoiceStateMachine = $this->createStub(WorkflowInterface::class);
        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $router = $this->createStub(RouterInterface::class);

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
        );

        $this->expectException(NotFoundHttpException::class);

        $action(Request::create('/pay/non-existent-uuid'), 'non-existent-uuid');
    }

    public function testInvoiceCannotBePaidRedirectsWithFlash(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Paid,
        ])->_real();

        $invoiceRepository = $this->createMock(InvoiceRepository::class);
        $invoiceRepository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($invoice);

        $paymentMethodRepository = $this->createStub(PaymentMethodRepository::class);

        $invoiceStateMachine = $this->createMock(WorkflowInterface::class);
        $invoiceStateMachine->expects(self::once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_PAY)
            ->willReturn(false);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_REMEMBERED')
            ->willReturn(true);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/456');

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
        );

        $request = Request::create('/pay/' . (string) $invoice->getUuid());
        $response = $action($request, (string) $invoice->getUuid());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/456', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_DANGER, $flashes);
        self::assertSame('payment.create.exception.invoice_cannot_be_paid', $flashes[FlashResponse::FLASH_DANGER]);
    }

    public function testOnlineCaptureBlockedInDemoMode(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'company' => $this->company,
            'client' => $client,
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ])->_real();

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
            'gatewayName' => 'stripe',
            'factoryName' => 'stripe',
            'internal' => false,
            'enabled' => true,
        ])->_real();

        $invoiceRepository = $this->createStub(InvoiceRepository::class);
        $invoiceRepository->method('findOneBy')->willReturn($invoice);

        $paymentMethodRepository = $this->createStub(PaymentMethodRepository::class);
        $paymentMethodRepository->method('getTotalMethodsConfigured')->willReturn(1);
        $paymentMethodRepository->method('findBy')->willReturn([]);

        $invoiceStateMachine = $this->createStub(WorkflowInterface::class);
        $invoiceStateMachine->method('can')->willReturn(true);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn(true);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');

        $form = $this->buildSubmittedForm([
            'amount' => BigNumber::of(100),
            'payment_method' => $paymentMethod,
            'capture_online' => true,
            'reference' => null,
            'notes' => null,
        ]);

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
            new ModeResolver('demo', 'demo@example.com', 'demo-password'),
            $this->buildFormFactory($form),
        );

        $paymentRepository = self::getContainer()->get('doctrine')->getRepository(Payment::class);
        $paymentCountBefore = $paymentRepository->count([]);

        $request = Request::create('/pay/' . (string) $invoice->getUuid(), 'POST');

        try {
            $action($request, (string) $invoice->getUuid());
            self::fail('Expected an AccessDeniedHttpException to be thrown.');
        } catch (AccessDeniedHttpException) {
            // expected
        }

        // The guard must trip before a `New` Payment row is persisted, so a blocked
        // online capture in demo mode must not leave behind an orphan Payment.
        self::assertSame($paymentCountBefore, $paymentRepository->count([]));
    }

    public function testOfflineCaptureUnaffectedInDemoMode(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        ContactFactory::createOne([
            'company' => $this->company,
            'client' => $client,
        ]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
        ])->_real();

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethodFactory::createOne([
            'company' => $this->company,
            'gatewayName' => 'bank_transfer',
            'factoryName' => PaymentMethod::FACTORY_OFFLINE,
            'internal' => false,
            'enabled' => true,
        ])->_real();

        $invoiceRepository = $this->createStub(InvoiceRepository::class);
        $invoiceRepository->method('findOneBy')->willReturn($invoice);

        $paymentMethodRepository = $this->createStub(PaymentMethodRepository::class);
        $paymentMethodRepository->method('getTotalMethodsConfigured')->willReturn(1);
        $paymentMethodRepository->method('findBy')->willReturn([]);

        $invoiceStateMachine = $this->createStub(WorkflowInterface::class);
        $invoiceStateMachine->method('can')->willReturn(true);

        $authorizationChecker = $this->createStub(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->willReturn(true);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_payments_index', self::anything())
            ->willReturn('/payments');

        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        $form = $this->buildSubmittedForm([
            'amount' => BigNumber::of(100),
            'payment_method' => $paymentMethod,
            'capture_online' => false,
            'reference' => null,
            'notes' => null,
        ]);

        $action = $this->buildAction(
            $paymentMethodRepository,
            $invoiceRepository,
            $invoiceStateMachine,
            $authorizationChecker,
            $router,
            new ModeResolver('demo', 'demo@example.com', 'demo-password'),
            $this->buildFormFactory($form),
            $eventDispatcher,
        );

        $request = Request::create('/pay/' . (string) $invoice->getUuid(), 'POST');
        $response = $action($request, (string) $invoice->getUuid());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/payments', $response->getTargetUrl());
    }
}
