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

namespace SolidInvoice\SaasBundle\Tests\Functional;

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
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS email-verification gate short-circuits the manual
 * invoice reminder action with a flash error and skips the mailer when
 * the gate is engaged, and lets the reminder proceed when the gate is open.
 *
 * @group functional
 */
final class InvoiceReminderGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGatedReminderShortCircuitsAndDoesNotSendEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $action = $this->buildAction(gated: true, mailer: $mailer);

        $invoice = $this->createPendingInvoice();

        $request = $this->createRequestWithCsrfToken();
        $response = $action($request, $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('email_verification.flash.send_reminder', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testUngatedReminderDispatchesEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $action = $this->buildAction(gated: false, mailer: $mailer);

        $invoice = $this->createPendingInvoice();

        $request = $this->createRequestWithCsrfToken();
        $response = $action($request, $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.manual_reminder.success', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    private function buildAction(bool $gated, MailerInterface $mailer): SendManualReminder
    {
        $container = self::getContainer();

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $logger = $this->createMock(LoggerInterface::class);

        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn($gated);

        $action = new SendManualReminder($mailer, $router, $logger, $gate);
        $action->setContainer($container);

        return $action;
    }

    private function createPendingInvoice(): \SolidInvoice\InvoiceBundle\Entity\Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'users' => [$contact],
        ]);

        return $invoice->_real();
    }

    private function createRequestWithCsrfToken(): Request
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = Request::create('/send-manual-reminder', 'POST');
        $request->setSession($session);

        $requestStack = self::getContainer()->get('request_stack');
        $requestStack->push($request);

        $csrfTokenManager = self::getContainer()->get('security.csrf.token_manager');
        $token = $csrfTokenManager->getToken('send_manual_reminder');
        $request->request->set('_token', $token->getValue());

        return $request;
    }
}
