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

use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\SendManualReminder;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

final class SendManualReminderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private function createOpenGate(): EmailVerificationGateInterface
    {
        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn(false);

        return $gate;
    }

    private function createClosedGate(): EmailVerificationGateInterface
    {
        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn(true);

        return $gate;
    }

    private function createRequestWithCsrfToken(): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/send-manual-reminder', 'POST');
        $request->setSession($session);

        // Push the request onto the RequestStack so CSRF token manager can access it
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $csrfTokenManager = self::getContainer()->get('security.csrf.token_manager');
        $token = $csrfTokenManager->getToken('send_manual_reminder');
        $request->request->set('_token', $token->getValue());

        return $request;
    }

    private function createRequestWithInvalidCsrfToken(): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/send-manual-reminder', 'POST');
        $request->setSession($session);

        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $request->request->set('_token', 'invalid_token');

        return $request;
    }

    private function executeGatedReminder(Request $request): RedirectResponse
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::callback(fn (array $params): bool => $params['id'] instanceof Ulid))
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');
        $logger->expects(self::never())->method('error');

        $action = new SendManualReminder($mailer, $router, $logger, $this->createClosedGate());
        $action->setContainer(self::getContainer());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        return $action($request, $invoice->_real());
    }

    public function testSendManualReminderSuccess(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::callback(fn (array $params): bool => $params['id'] instanceof Ulid))
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Manual reminder sent for invoice', self::anything());

        $action = new SendManualReminder($mailer, $router, $logger, $this->createOpenGate());
        $action->setContainer(self::getContainer());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        $request = $this->createRequestWithCsrfToken();
        $response = $action($request, $invoice->_real());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.manual_reminder.success', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    public function testSendManualReminderWithNoContacts(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::callback(fn (array $params): bool => $params['id'] instanceof Ulid))
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');

        $action = new SendManualReminder($mailer, $router, $logger, $this->createOpenGate());
        $action->setContainer(self::getContainer());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [], // No contacts
        ]);

        $request = $this->createRequestWithCsrfToken();
        $response = $action($request, $invoice->_real());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.manual_reminder.error.no_contacts', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testSendManualReminderWithMailerFailure(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->willThrowException(new TransportException('SMTP connection failed'));

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::callback(fn (array $params): bool => $params['id'] instanceof Ulid))
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');
        $logger->expects(self::once())
            ->method('error')
            ->with('Failed to send manual reminder', self::anything());

        $action = new SendManualReminder($mailer, $router, $logger, $this->createOpenGate());
        $action->setContainer(self::getContainer());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        $request = $this->createRequestWithCsrfToken();
        $response = $action($request, $invoice->_real());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.manual_reminder.error.send_failed', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testSendManualReminderWithInvalidCsrfToken(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('_invoices_view', self::callback(fn (array $params): bool => $params['id'] instanceof Ulid))
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');
        $logger->expects(self::never())->method('error');

        $action = new SendManualReminder($mailer, $router, $logger, $this->createOpenGate());
        $action->setContainer(self::getContainer());

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        // Create a request with an invalid CSRF token
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/send-manual-reminder', 'POST');
        $request->setSession($session);

        // Push the request onto the RequestStack so CSRF token manager can access it
        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $request->request->set('_token', 'invalid_token');

        $response = $action($request, $invoice->_real());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.manual_reminder.error.invalid_csrf', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testGatedWithInvalidCsrfReturnsCsrfError(): void
    {
        $response = $this->executeGatedReminder($this->createRequestWithInvalidCsrfToken());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.manual_reminder.error.invalid_csrf', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testGatedWithValidCsrfReturnsGateError(): void
    {
        $response = $this->executeGatedReminder($this->createRequestWithCsrfToken());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('email_verification.flash.send_reminder', $flashes[FlashResponse::FLASH_ERROR]);
    }
}
