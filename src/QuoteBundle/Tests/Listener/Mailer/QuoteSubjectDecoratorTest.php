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
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteSubjectListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;

class QuoteSubjectDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('quote/email_subject')
            ->andReturn('New Quote: #{id}');

        $listener = new QuoteSubjectListener($config);
        $quote = new Quote();
        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertSame('New Quote: #' . $quote->getId(), $message->getSubject());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuoteSubjectListener::getSubscribedEvents()));
    }
}
