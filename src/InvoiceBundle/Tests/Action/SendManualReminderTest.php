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
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Action\SendManualReminder;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

final class SendManualReminderTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

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

        $action = new SendManualReminder($mailer, $router, $logger);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => 'pending',
            'users' => [$contact],
        ]);

        $response = $action($invoice->_real());

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

        $action = new SendManualReminder($mailer, $router, $logger);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => 'pending',
            'users' => [], // No contacts
        ]);

        $response = $action($invoice->_real());

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

        $action = new SendManualReminder($mailer, $router, $logger);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => 'pending',
            'users' => [$contact],
        ]);

        $response = $action($invoice->_real());

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('invoice.manual_reminder.error.send_failed', $flashes[FlashResponse::FLASH_ERROR]);
    }
}
