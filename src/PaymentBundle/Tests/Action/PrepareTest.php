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

use Payum\Core\Registry\RegistryInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Action\Prepare;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    ): Prepare {
        $action = new Prepare(
            $invoiceStateMachine,
            $paymentMethodRepository,
            $authorizationChecker,
            self::getContainer()->get(TokenStorageInterface::class),
            self::getContainer()->get(FormFactoryInterface::class),
            self::getContainer()->get(EventDispatcherInterface::class),
            self::getContainer()->get(RegistryInterface::class),
            $router,
            self::getContainer()->get(CompanySelector::class),
            $invoiceRepository,
        );

        $action->setDoctrine(self::getContainer()->get('doctrine'));

        return $action;
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
}
