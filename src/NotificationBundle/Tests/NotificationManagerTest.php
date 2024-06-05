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

namespace SolidInvoice\NotificationBundle\Tests;

use Hamcrest\Core\IsEqual;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\NotificationBundle\Exception\InvalidNotificationMessageException;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Transport\Dsn;
use Twig\Environment;

/**
 * @covers \SolidInvoice\NotificationBundle\Notification\NotificationManager
 */
final class NotificationManagerTest extends TestCase
{
    use EnsureApplicationInstalled;
    use FakerTestTrait;
    use MockeryPHPUnitIntegration;

    private NotificationManager $notificationManager;

    private NotifierInterface | M\MockInterface $notifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifier = M::mock(NotifierInterface::class);

        $this->notificationManager = new NotificationManager(
            $this->notifier,
            static::getContainer()->get('doctrine')->getRepository(UserNotification::class),
            new ServiceLocator([]),
        );
    }

    public function testMessageWithoutAttribute(): void
    {
        $class = new class() extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $this->expectException(InvalidNotificationMessageException::class);
        $this->expectExceptionMessage('The notification message "' . $class::class . '" must have the ' . AsNotification::class . ' set.');

        $this->notificationManager->sendNotification($class);
    }

    public function testSendEmailNotification(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email = $this->getFaker()->email();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('password');

        $userNotification = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(true)
            ->setUser($user);

        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($userNotification);
        $em->persist($user);
        $em->flush();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email, '')))
            ->once();

        $this->notificationManager->sendNotification($class);
        self::assertSame(['email'], $class->getChannels(new Recipient($email, '')));
    }

    public function testSendNotificationWithNoUsers(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email = $this->getFaker()->email();

        $this->notifier
            ->expects('send')
            ->never();

        $this->notificationManager->sendNotification($class);
        self::assertSame([], $class->getChannels(new Recipient($email, '')));
    }

    public function testSendWithNoTransports(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email = $this->getFaker()->email();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('password');

        $userNotification = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(false)
            ->setUser($user);

        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($userNotification);
        $em->persist($user);
        $em->flush();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email, '')))
            ->once();

        $this->notificationManager->sendNotification($class);
        self::assertSame([], $class->getChannels(new Recipient($email, '')));
    }

    public function testSendTransportNotification(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email = $this->getFaker()->email();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('password');

        $transportSetting = (new TransportSetting())
            ->setName('Test Foo')
            ->setTransport('FooBar')
            ->setUser($user);

        $userNotification = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(false)
            ->setUser($user)
            ->addTransport($transportSetting);

        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->persist($transportSetting);
        $em->persist($userNotification);
        $em->flush();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email, '')))
            ->once();

        $configurator = M::mock(ConfiguratorInterface::class);
        $configurator
            ->expects('getType')
            ->once()
            ->andReturn('chatter');

        $notificationManager = new NotificationManager(
            $this->notifier,
            static::getContainer()->get('doctrine')->getRepository(UserNotification::class),
            new ServiceLocator(['FooBar' => static fn () => $configurator]),
        );

        $notificationManager->sendNotification($class);
        self::assertSame(['chat/' . $transportSetting->getId()->toString()], $class->getChannels(new Recipient($email, '')));
    }

    public function testSendTransportNotificationWithMultipleUsers(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email1 = $this->getFaker()->email();
        $email2 = $this->getFaker()->email();

        $user1 = (new User())
            ->setEmail($email1)
            ->setPassword('password');
        $user2 = (new User())
            ->setEmail($email2)
            ->setPassword('password');

        $transportSetting1 = (new TransportSetting())
            ->setName('Test Foo')
            ->setTransport('FooBar')
            ->setUser($user1);
        $transportSetting2 = (new TransportSetting())
            ->setName('Test Foo')
            ->setTransport('FooBar')
            ->setUser($user2);

        $userNotification1 = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(false)
            ->setUser($user1)
            ->addTransport($transportSetting1);
        $userNotification2 = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(false)
            ->setUser($user2)
            ->addTransport($transportSetting2);

        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user1);
        $em->persist($user2);
        $em->persist($transportSetting1);
        $em->persist($transportSetting2);
        $em->persist($userNotification1);
        $em->persist($userNotification2);
        $em->flush();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email1, '')))
            ->once();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email2, '')))
            ->once();

        $configurator = M::mock(ConfiguratorInterface::class);
        $configurator
            ->expects('getType')
            ->twice()
            ->andReturn('chatter');

        $notificationManager = new NotificationManager(
            $this->notifier,
            static::getContainer()->get('doctrine')->getRepository(UserNotification::class),
            new ServiceLocator(['FooBar' => static fn () => $configurator]),
        );

        $notificationManager->sendNotification($class);
        self::assertSame(['chat/' . $transportSetting2->getId()->toString()], $class->getChannels(new Recipient($email2, '')));
    }

    public function testSendMultipleTransportNotification(): void
    {
        $class = new #[AsNotification(name: 'test_event')] class extends NotificationMessage {
            public function getTextContent(Environment $twig): string
            {
                return '';
            }
        };

        $email = $this->getFaker()->email();

        $user = (new User())
            ->setEmail($email)
            ->setPassword('password');

        $transportSetting = (new TransportSetting())
            ->setName('Test Foo')
            ->setTransport('FooBar')
            ->setUser($user)
        ;

        $transportSetting2 = (new TransportSetting())
            ->setName('Test Foos')
            ->setTransport('FooBars')
            ->setUser($user)
        ;

        $userNotification = (new UserNotification())
            ->setEvent('test_event')
            ->setEmail(true)
            ->setUser($user)
            ->addTransport($transportSetting)
            ->addTransport($transportSetting2)
        ;

        $em = static::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->persist($transportSetting);
        $em->persist($transportSetting2);
        $em->persist($userNotification);
        $em->flush();

        $this->notifier
            ->expects('send')
            ->with($class, IsEqual::equalTo(new Recipient($email, '')))
            ->once();

        $configurator = new class() implements ConfiguratorInterface {
            public static function getName(): string
            {
                return 'Test Foo';
            }

            public static function getType(): string
            {
                return 'chatter';
            }

            public function getForm(): string
            {
                return '';
            }

            public function configure(array $config): Dsn
            {
                return new Dsn('');
            }
        };

        $configurator2 = new class() implements ConfiguratorInterface {
            public static function getName(): string
            {
                return 'Test Foo';
            }

            public static function getType(): string
            {
                return 'texter';
            }

            public function getForm(): string
            {
                return '';
            }

            public function configure(array $config): Dsn
            {
                return new Dsn('');
            }
        };

        $notificationManager = new NotificationManager(
            $this->notifier,
            static::getContainer()->get('doctrine')->getRepository(UserNotification::class),
            new ServiceLocator(['FooBar' => static fn () => $configurator, 'FooBars' => static fn () => $configurator2]),
        );

        $notificationManager->sendNotification($class);
        self::assertSame(
            [
                'email',
                'chat/' . $transportSetting->getId()->toString(),
                'sms/' . $transportSetting2->getId()->toString(),
            ],
            $class->getChannels(
                new Recipient($email, '')
            )
        );
    }
}
