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
    use MockeryPHPUnitIntegration;

    public function testWithoutBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/bcc_address')
            ->andReturnNull();

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertSame([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        static::assertSame([], $message->getBcc());
    }

    public function testWithBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/bcc_address')
            ->andReturn('bcc@example.com');

        $listener = new QuoteReceiverListener($config);
        $quote = new Quote();
        $quote->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertSame([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        static::assertSame([new Address('bcc@example.com')], $message->getBcc());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteReceiverListener::getSubscribedEvents()));
    }
}
