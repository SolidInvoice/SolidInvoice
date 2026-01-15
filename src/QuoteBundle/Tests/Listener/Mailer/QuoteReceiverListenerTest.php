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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteReceiverListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

class QuoteReceiverListenerTest extends TestCase
{
    public function testWithoutBcc(): void
    {
        /** @var SystemConfig&MockObject $config */
        $config = $this->createMock(SystemConfig::class);
        $config->method('get')
            ->with('quote/bcc_address')
            ->willReturn(null);

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertSame([], $message->getBcc());
    }

    public function testWithBcc(): void
    {
        /** @var SystemConfig&MockObject $config */
        $config = $this->createMock(SystemConfig::class);
        $config->method('get')
            ->with('quote/bcc_address')
            ->willReturn('bcc@example.com');

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        self::assertEquals([new Address('bcc@example.com')], $message->getBcc());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteReceiverListener::getSubscribedEvents()));
    }
}
