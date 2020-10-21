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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Mailer;
use SolidInvoice\MailerBundle\MessageProcessorInterface;
use SolidInvoice\MailerBundle\MessageSentResponse;

class MailerTest extends TestCase
{
    public function testSend()
    {
        /** @var MessageProcessorInterface|MockObject $processor */
        $processor = $this->createMock(MessageProcessorInterface::class);
        $mailer = new Mailer($processor);
        $mail = new \Swift_Message();

        $processor->expects(static::at(0))
            ->method('process')
            ->with($mail)
            ->willReturn($result = new MessageSentResponse());

        static::assertSame($result, $mailer->send($mail));
        static::assertTrue($result->isSuccess());
    }

    public function testSendWithFailedRecipients()
    {
        /** @var MessageProcessorInterface|MockObject $processor */
        $processor = $this->createMock(MessageProcessorInterface::class);
        $mailer = new Mailer($processor);
        $mail = new \Swift_Message();

        $processor->expects(static::at(0))
            ->method('process')
            ->with($mail)
            ->willReturn($result = new MessageSentResponse(['user1' => 'user1@foo.com']));

        static::assertSame($result, $mailer->send($mail));
        static::assertFalse($result->isSuccess());
    }
}
