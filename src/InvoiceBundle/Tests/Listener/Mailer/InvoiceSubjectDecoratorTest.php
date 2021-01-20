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

namespace SolidInvoice\InvoiceBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoiceSubjectListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;

class InvoiceSubjectDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/email_subject')
            ->andReturn('New Invoice: #{id}');

        $listener = new InvoiceSubjectListener($config);
        $message = new InvoiceEmail(new Invoice());
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertSame('New Invoice: #', $message->getSubject());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(InvoiceSubjectListener::getSubscribedEvents()));
    }
}
