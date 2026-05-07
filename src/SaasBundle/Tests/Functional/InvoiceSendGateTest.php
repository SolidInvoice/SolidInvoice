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

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\Transition\Send;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS email-verification gate short-circuits the invoice send
 * action with a flash error and skips the mailer when the gate is engaged,
 * and lets the send proceed when the gate is open.
 *
 * @group functional
 */
final class InvoiceSendGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGatedSendShortCircuitsAndDoesNotSendEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $action = $this->buildAction(gated: true, mailer: $mailer);

        $invoice = $this->createPendingInvoice();

        $response = $action(Request::create('/invoices/action/send/' . $invoice->getId()), $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('email_verification.flash.send_invoice', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testUngatedSendDispatchesEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $action = $this->buildAction(gated: false, mailer: $mailer);

        $invoice = $this->createPendingInvoice();

        $response = $action(Request::create('/invoices/action/send/' . $invoice->getId()), $invoice);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.transition.action.sent', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    private function buildAction(bool $gated, MailerInterface $mailer): Send
    {
        $container = self::getContainer();

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn($gated);

        $workflow = $container->get('state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        $action = new Send($workflow, $mailer, $router, $gate);
        $action->setDoctrine($container->get('doctrine'));

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
}
