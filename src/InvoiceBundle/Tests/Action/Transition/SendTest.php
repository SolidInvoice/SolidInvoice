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

namespace SolidInvoice\InvoiceBundle\Tests\Action\Transition;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Action\Transition\Send;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class SendTest extends TestCase
{
    private function createGate(bool $gated): EmailVerificationGateInterface
    {
        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn($gated);

        return $gate;
    }

    private function createLogger(): LoggerInterface
    {
        return $this->createStub(LoggerInterface::class);
    }

    public function testSendWithNoContactsReturnsErrorFlash(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('can');
        $workflow->expects($this->never())->method('apply');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $action = new Send($workflow, $mailer, $router, $this->createGate(false), $this->createLogger());

        $invoice = new Invoice();
        // No users added — getUsers()->isEmpty() === true

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.send.no_recipients', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testSendWithEmailGateBlockedReturnsErrorFlash(): void
    {
        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('can');
        $workflow->expects($this->never())->method('apply');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $action = new Send($workflow, $mailer, $router, $this->createGate(true), $this->createLogger());

        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com'));

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('email_verification.flash.send_invoice', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testSendWithContactsAndPendingStatusDispatchesEmail(): void
    {
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com'));
        $invoice->setStatus(InvoiceStatus::Pending);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('apply');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with(self::isInstanceOf(InvoiceEmail::class));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('persist')->with($invoice);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())->method('getManager')->willReturn($em);

        $action = new Send($workflow, $mailer, $router, $this->createGate(false), $this->createLogger());
        $action->setDoctrine($doctrine);

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.transition.action.sent', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    public function testSendWithPaidStatusSkipsTransitionAndDispatchesEmail(): void
    {
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com'));
        $invoice->setStatus(InvoiceStatus::Paid);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_ACCEPT)
            ->willReturn(false);
        $workflow->expects($this->never())->method('apply');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with(self::isInstanceOf(InvoiceEmail::class));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('persist')->with($invoice);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())->method('getManager')->willReturn($em);

        $action = new Send($workflow, $mailer, $router, $this->createGate(false), $this->createLogger());
        $action->setDoctrine($doctrine);

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.transition.action.sent', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    public function testSendWithContactsAndNonPendingStatusAppliesTransition(): void
    {
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com'));
        $invoice->setStatus(InvoiceStatus::Draft);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->once())
            ->method('can')
            ->with($invoice, Graph::TRANSITION_ACCEPT)
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('apply')
            ->with($invoice, Graph::TRANSITION_ACCEPT);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with(self::isInstanceOf(InvoiceEmail::class));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->willReturn('/invoices/view/123');

        $em = $this->createStub(ObjectManager::class);
        $em->method('persist');
        $em->method('flush');

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine->method('getManager')->willReturn($em);

        $action = new Send($workflow, $mailer, $router, $this->createGate(false), $this->createLogger());
        $action->setDoctrine($doctrine);

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('invoice.transition.action.sent', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    public function testSendReturnsErrorFlashOnTransportException(): void
    {
        $invoice = new Invoice();
        $invoice->addUser(new Contact()->setEmail('test@example.com'));
        $invoice->setStatus(InvoiceStatus::Pending);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->never())->method('apply');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('Connection refused'));

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('_invoices_view', self::anything())
            ->willReturn('/invoices/view/123');

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())->method('persist')->with($invoice);
        $em->expects($this->once())->method('flush');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->once())->method('getManager')->willReturn($em);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $action = new Send($workflow, $mailer, $router, $this->createGate(false), $logger);
        $action->setDoctrine($doctrine);

        $response = $action(new Request(), $invoice);

        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/invoices/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.email.send_failed', $flashes[FlashResponse::FLASH_ERROR]);
    }
}
