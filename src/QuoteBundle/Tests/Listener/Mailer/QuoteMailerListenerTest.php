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
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteMailerListener;
use Symfony\Component\Mailer\MailerInterface;

class QuoteMailerListenerTest extends TestCase
{
    public function testListener(): void
    {
        $quote = new Quote();

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(QuoteEmail::class));

        $listener = new QuoteMailerListener($mailer);

        $quote->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $listener->onQuoteSend(new QuoteEvent($quote));
    }

    public function testEvents(): void
    {
        self::assertSame([QuoteEvents::QUOTE_POST_SEND], \array_keys(QuoteMailerListener::getSubscribedEvents()));
    }
}
