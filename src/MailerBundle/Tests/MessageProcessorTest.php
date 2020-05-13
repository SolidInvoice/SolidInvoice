<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MailerBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Decorator\VerificationMessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\MailerBundle\Event\MessageResultEvent;
use SolidInvoice\MailerBundle\MessageProcessor;
use SolidInvoice\MailerBundle\MessageSentResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageProcessorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testProcessWithSuccess()
    {
        $decorator1 = M::mock(MessageDecorator::class);
        $decorator2 = M::mock(MessageDecorator::class, VerificationMessageDecorator::class);
        $decorator3 = M::mock(MessageDecorator::class, VerificationMessageDecorator::class);

        $decorator1->shouldReceive('decorate')
            ->once();

        $decorator2->shouldReceive('shouldDecorate')
            ->once()
            ->andReturnFalse();

        $decorator2->shouldNotReceive('decorate');

        $decorator3->shouldReceive('shouldDecorate')
            ->once()
            ->andReturnTrue();

        $decorator3->shouldReceive('decorate')
            ->once();

        $decorators = [
            $decorator1,
            $decorator2,
            $decorator3,
        ];

        $message = new \Swift_Message();

        $mailer = M::mock(\Swift_Mailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->with(
                $message,
                \Mockery::on(function (&$failedRecipients) {
                    $failedRecipients = [];

                    return true;
                })
            );

        $eventDispatcher = M::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.decorate', M::type(MessageEvent::class));

        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.before_send', M::type(MessageEvent::class));

        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.after_send', M::type(MessageResultEvent::class));

        $processor = new MessageProcessor($mailer, $eventDispatcher, $decorators);

        $result = $processor->process($message, Context::create());

        $this->assertInstanceOf(MessageSentResponse::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    public function testProcessWithFail()
    {
        $decorator1 = M::mock(MessageDecorator::class);
        $decorator2 = M::mock(MessageDecorator::class, VerificationMessageDecorator::class);
        $decorator3 = M::mock(MessageDecorator::class, VerificationMessageDecorator::class);

        $decorator1->shouldReceive('decorate')
            ->once();

        $decorator2->shouldReceive('shouldDecorate')
            ->once()
            ->andReturnFalse();

        $decorator2->shouldNotReceive('decorate');

        $decorator3->shouldReceive('shouldDecorate')
            ->once()
            ->andReturnTrue();

        $decorator3->shouldReceive('decorate')
            ->once();

        $decorators = [
            $decorator1,
            $decorator2,
            $decorator3,
        ];

        $message = new \Swift_Message();
        $message->setTo('foo@bar.com');

        $mailer = M::mock(\Swift_Mailer::class);
        $mailer->shouldReceive('send')
            ->once()
            ->with(
                $message,
                \Mockery::on(function (&$failedRecipients) {
                    $failedRecipients = ['foo@bar.com'];

                    return true;
                })
            );

        $eventDispatcher = M::mock(EventDispatcherInterface::class);
        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.decorate', M::type(MessageEvent::class));

        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.before_send', M::type(MessageEvent::class));

        $eventDispatcher->shouldReceive('dispatch')
            ->once()
            ->with('message.after_send', M::type(MessageResultEvent::class));

        $processor = new MessageProcessor($mailer, $eventDispatcher, $decorators);

        $result = $processor->process($message, Context::create());

        $this->assertInstanceOf(MessageSentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
    }
}
