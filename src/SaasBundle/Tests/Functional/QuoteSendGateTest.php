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
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Action\Transition\Send;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Mailer\QuoteMailer;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the SaaS email-verification gate short-circuits the quote send
 * action with a flash error and skips the mailer when the gate is engaged,
 * and lets the send proceed when the gate is open.
 *
 * @group functional
 */
final class QuoteSendGateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testGatedRequestDoesNotSendQuote(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $action = $this->buildAction(gated: true, mailer: $mailer);

        $quote = $this->createPendingQuote();

        $response = $action(Request::create('/quotes/action/send/' . $quote->getId()), $quote);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);
        self::assertSame('/quotes/view/123', $response->getTargetUrl());

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_ERROR, $flashes);
        self::assertSame('email_verification.flash.send_quote', $flashes[FlashResponse::FLASH_ERROR]);
    }

    public function testUngatedRequestSendsQuote(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $action = $this->buildAction(gated: false, mailer: $mailer);

        $quote = $this->createPendingQuote();

        $response = $action(Request::create('/quotes/action/send/' . $quote->getId()), $quote);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertInstanceOf(FlashResponse::class, $response);

        $flashes = iterator_to_array($response->getFlash());
        self::assertArrayHasKey(FlashResponse::FLASH_SUCCESS, $flashes);
        self::assertSame('quote.transition.action.sent', $flashes[FlashResponse::FLASH_SUCCESS]);
    }

    private function buildAction(bool $gated, MailerInterface $mailer): Send
    {
        $container = self::getContainer();

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')
            ->with('_quotes_view', self::anything())
            ->willReturn('/quotes/view/123');

        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturn($gated);

        $workflow = $container->get('state_machine.quote');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);

        $notification = $container->get(NotificationManager::class);
        self::assertInstanceOf(NotificationManager::class, $notification);

        $quoteMailer = new QuoteMailer($workflow, $mailer, $notification);

        return new Send($quoteMailer, $router, $gate);
    }

    private function createPendingQuote(): \SolidInvoice\QuoteBundle\Entity\Quote
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $quote = QuoteFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => QuoteStatus::Pending,
            'users' => [$contact],
        ]);

        return $quote->_real();
    }
}
