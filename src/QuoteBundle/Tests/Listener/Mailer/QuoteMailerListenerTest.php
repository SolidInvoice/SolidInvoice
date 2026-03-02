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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteMailerListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

class QuoteMailerListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $quote = new Quote();

        $mailer = M::spy(MailerInterface::class);
        $logger = M::spy(LoggerInterface::class);
        $requestStack = new RequestStack();

        $listener = new QuoteMailerListener($mailer, $logger, $requestStack);

        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $listener->onQuoteSend(new QuoteEvent($quote));

        $mailer->shouldHaveReceived('send')
            ->with(M::type(QuoteEmail::class));
    }

    public function testListenerHandlesTransportException(): void
    {
        $quote = new Quote();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->andThrow(new TransportException('Connection refused'));

        $logger = M::spy(LoggerInterface::class);

        $flashBag = new FlashBag();
        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new QuoteMailerListener($mailer, $logger, $requestStack);

        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));

        // Should not throw - exception is caught and logged
        $listener->onQuoteSend(new QuoteEvent($quote));

        $logger->shouldHaveReceived('error')
            ->with(M::pattern('/Failed to send quote email/'), M::type('array'));

        self::assertSame(['quote.email.send_failed'], $flashBag->get('error'));
    }

    public function testEvents(): void
    {
        self::assertSame([QuoteEvents::QUOTE_POST_SEND], \array_keys(QuoteMailerListener::getSubscribedEvents()));
    }
}
