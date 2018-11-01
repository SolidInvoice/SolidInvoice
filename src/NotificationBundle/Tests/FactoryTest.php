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

namespace SolidInvoice\NotificationBundle\Tests;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Mailer\Exception\UnexpectedFormatException;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\NotificationBundle\Notification\Factory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessageInterface;
use SolidInvoice\NotificationBundle\Notification\SwiftMailerNotification;
use SolidInvoice\NotificationBundle\Notification\TwilioNotification;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FactoryTest extends TestCase
{
    use FakerTestTrait,
        MockeryPHPUnitIntegration;

    public function testCreateSmsNotification()
    {
        $faker = $this->getFaker();
        $templating = M::mock(EngineInterface::class);
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($templating, $translator, $settings);

        $message = M::mock(NotificationMessageInterface::class);
        $messageText = $faker->text;
        $message->shouldReceive('getTextContent')
            ->with($templating)
            ->andReturn($messageText);

        $phoneNumber = $faker->phoneNumber;
        $notification = $factory->createSmsNotification($phoneNumber, $message);
        $this->assertInstanceOf(TwilioNotification::class, $notification);
        $this->assertSame($messageText, $notification->getMessage());
        $this->assertSame($phoneNumber, $notification->getRecipientNumber());
    }

    public function testCreateHtmlEmailNotification()
    {
        $faker = $this->getFaker();
        $templating = M::mock(EngineInterface::class);
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($templating, $translator, $settings);

        $fromEmail = $faker->email;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_address')
            ->andReturn($fromEmail);

        $fromName = $faker->name;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_name')
            ->andReturn($fromName);

        $settings->shouldReceive('get')
            ->once()
            ->with('email/format')
            ->andReturn('html');

        $message = M::mock(NotificationMessageInterface::class);
        $user = new User();
        $toEmail = $faker->email;
        $user->setEmail($toEmail);
        $toName = $faker->userName;
        $user->setUsername($toName);
        $message->shouldReceive('getUsers')
            ->once()
            ->andReturn([$user]);

        $subject = $faker->text;
        $message->shouldReceive('getSubject')
            ->once()
            ->with($translator)
            ->andReturn($subject);

        $body = $faker->randomHtml();
        $message->shouldReceive('getHtmlContent')
            ->once()
            ->with($templating)
            ->andReturn($body);

        $notification = $factory->createEmailNotification($message);

        $this->assertInstanceOf(SwiftMailerNotification::class, $notification);
        /** @var \Swift_Message $swiftMessage */
        $swiftMessage = $notification->getMessage();
        $this->assertInstanceOf(\Swift_Message::class, $swiftMessage);
        $this->assertSame($body, $swiftMessage->getBody());
        $this->assertSame($subject, $swiftMessage->getSubject());
        $this->assertSame('text/html', $swiftMessage->getContentType());
        $this->assertSame([$fromEmail => $fromName], $swiftMessage->getFrom());
        $this->assertSame([$toEmail => $toName], $swiftMessage->getTo());
    }

    public function testCreateTextEmailNotification()
    {
        $faker = $this->getFaker();
        $templating = M::mock(EngineInterface::class);
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($templating, $translator, $settings);

        $fromEmail = $faker->email;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_address')
            ->andReturn($fromEmail);

        $fromName = $faker->name;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_name')
            ->andReturn($fromName);

        $settings->shouldReceive('get')
            ->once()
            ->with('email/format')
            ->andReturn('text');

        $message = M::mock(NotificationMessageInterface::class);
        $user = new User();
        $toEmail = $faker->email;
        $user->setEmail($toEmail);
        $toName = $faker->userName;
        $user->setUsername($toName);
        $message->shouldReceive('getUsers')
            ->once()
            ->andReturn([$user]);

        $subject = $faker->text;
        $message->shouldReceive('getSubject')
            ->once()
            ->with($translator)
            ->andReturn($subject);

        $body = $faker->text;
        $message->shouldReceive('getTextContent')
            ->once()
            ->with($templating)
            ->andReturn($body);

        $notification = $factory->createEmailNotification($message);

        $this->assertInstanceOf(SwiftMailerNotification::class, $notification);
        /** @var \Swift_Message $swiftMessage */
        $swiftMessage = $notification->getMessage();
        $this->assertInstanceOf(\Swift_Message::class, $swiftMessage);
        $this->assertSame($body, $swiftMessage->getBody());
        $this->assertSame($subject, $swiftMessage->getSubject());
        $this->assertSame('text/plain', $swiftMessage->getContentType());
        $this->assertSame([$fromEmail => $fromName], $swiftMessage->getFrom());
        $this->assertSame([$toEmail => $toName], $swiftMessage->getTo());
    }

    public function testCreateMultipleFormatEmailNotification()
    {
        $faker = $this->getFaker();
        $templating = M::mock(EngineInterface::class);
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($templating, $translator, $settings);

        $fromEmail = $faker->email;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_address')
            ->andReturn($fromEmail);

        $fromName = $faker->name;
        $settings->shouldReceive('get')
            ->once()
            ->with('email/from_name')
            ->andReturn($fromName);

        $settings->shouldReceive('get')
            ->once()
            ->with('email/format')
            ->andReturn('both');

        $message = M::mock(NotificationMessageInterface::class);
        $user = new User();
        $toEmail = $faker->email;
        $user->setEmail($toEmail);
        $toName = $faker->userName;
        $user->setUsername($toName);
        $message->shouldReceive('getUsers')
            ->once()
            ->andReturn([$user]);

        $subject = $faker->text;
        $message->shouldReceive('getSubject')
            ->once()
            ->with($translator)
            ->andReturn($subject);

        $htmlBody = $faker->randomHtml();
        $message->shouldReceive('getHtmlContent')
            ->once()
            ->with($templating)
            ->andReturn($htmlBody);

        $textBody = $faker->text;
        $message->shouldReceive('getTextContent')
            ->once()
            ->with($templating)
            ->andReturn($textBody);

        $notification = $factory->createEmailNotification($message);

        $this->assertInstanceOf(SwiftMailerNotification::class, $notification);
        /** @var \Swift_Message $swiftMessage */
        $swiftMessage = $notification->getMessage();
        $this->assertInstanceOf(\Swift_Message::class, $swiftMessage);
        $this->assertCount(1, $swiftMessage->getChildren());

        $this->assertSame($htmlBody, $swiftMessage->getBody());

        $this->assertSame($textBody, $swiftMessage->getChildren()[0]->getBody());
        $this->assertSame('text/plain', $swiftMessage->getChildren()[0]->getContentType());

        $this->assertSame($subject, $swiftMessage->getSubject());
        $this->assertSame('multipart/alternative', $swiftMessage->getContentType());
        $this->assertSame([$fromEmail => $fromName], $swiftMessage->getFrom());
        $this->assertSame([$toEmail => $toName], $swiftMessage->getTo());
    }
}
