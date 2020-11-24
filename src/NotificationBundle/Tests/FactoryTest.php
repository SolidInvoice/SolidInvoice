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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\NotificationBundle\Notification\Factory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessageInterface;
use SolidInvoice\NotificationBundle\Notification\SwiftMailerNotification;
use SolidInvoice\NotificationBundle\Notification\TwilioNotification;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class FactoryTest extends TestCase
{
    use FakerTestTrait;
    use MockeryPHPUnitIntegration;

    public function testCreateSmsNotification()
    {
        $faker = $this->getFaker();
        $twig = new Environment(new ArrayLoader());
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($twig, $translator, $settings);

        $message = M::mock(NotificationMessageInterface::class);
        $messageText = $faker->text;
        $message->shouldReceive('getTextContent')
            ->with($twig)
            ->andReturn($messageText);

        $phoneNumber = $faker->phoneNumber;
        $notification = $factory->createSmsNotification($phoneNumber, $message);
        static::assertInstanceOf(TwilioNotification::class, $notification);
        static::assertSame($messageText, $notification->getMessage());
        static::assertSame($phoneNumber, $notification->getRecipientNumber());
    }

    public function testCreateMultipleFormatEmailNotification()
    {
        $faker = $this->getFaker();
        $twig = new Environment(new ArrayLoader());
        $translator = M::mock(TranslatorInterface::class);
        $settings = M::mock(SystemConfig::class);
        $factory = new Factory($twig, $translator, $settings);

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
            ->with($twig)
            ->andReturn($htmlBody);

        $textBody = $faker->text;
        $message->shouldReceive('getTextContent')
            ->once()
            ->with($twig)
            ->andReturn($textBody);

        $notification = $factory->createEmailNotification($message);

        static::assertInstanceOf(SwiftMailerNotification::class, $notification);
        /** @var Swift_Message $swiftMessage */
        $swiftMessage = $notification->getMessage();
        static::assertInstanceOf(Swift_Message::class, $swiftMessage);
        static::assertCount(1, $swiftMessage->getChildren());

        static::assertSame($htmlBody, $swiftMessage->getBody());

        static::assertSame($textBody, $swiftMessage->getChildren()[0]->getBody());
        static::assertSame('text/plain', $swiftMessage->getChildren()[0]->getContentType());

        static::assertSame($subject, $swiftMessage->getSubject());
        static::assertSame('multipart/alternative', $swiftMessage->getContentType());
        static::assertSame([$fromEmail => $fromName], $swiftMessage->getFrom());
        static::assertSame([$toEmail => $toName], $swiftMessage->getTo());
    }
}
