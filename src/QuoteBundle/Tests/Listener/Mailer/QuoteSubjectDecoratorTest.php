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
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteSubjectListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;

class QuoteSubjectDecoratorTest extends TestCase
{
    public function testListener(): void
    {
        /** @var SystemConfig&MockObject $config */
        $config = $this->createMock(SystemConfig::class);
        $config->method('get')
            ->with('quote/email_subject')
            ->willReturn('New Quote: #{id}');

        $listener = new QuoteSubjectListener($config);
        $quote = new Quote();
        $quote->setQuoteId('123');
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertSame('New Quote: #123', $message->getSubject());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteSubjectListener::getSubscribedEvents()));
    }
}
