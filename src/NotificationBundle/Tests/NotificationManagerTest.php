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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Hamcrest\Core\IsInstanceOf;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Namshi\Notificator\Manager;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\NotificationBundle\Notification\ChainedNotification;
use SolidInvoice\NotificationBundle\Notification\Factory;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\NotificationBundle\Notification\NotificationMessageInterface;
use SolidInvoice\NotificationBundle\Notification\SwiftMailerNotification;
use SolidInvoice\NotificationBundle\Notification\TwilioNotification;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;

class NotificationManagerTest extends TestCase
{
    use FakerTestTrait;
    use
        MockeryPHPUnitIntegration;

    public function testSendEmailNotification()
    {
        $factory = M::mock(Factory::class);
        $settings = M::mock(SystemConfig::class);
        $manager = M::mock(Manager::class);
        $doctrine = M::mock(ManagerRegistry::class);
        $em = M::mock(ObjectManager::class);
        $doctrine->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $users = [];

        $user1 = new User();
        $users[] = $user1;

        $repository = M::mock(ObjectRepository::class);

        $em->shouldReceive('getRepository')
            ->once()
            ->with(User::class)
            ->andReturn($repository);

        $repository->shouldReceive('findAll')
            ->once()
            ->andReturn($users);

        $settings->shouldReceive('get')
            ->once()
            ->with('notification/create_invoice')
            ->andReturn('{"email": true, "sms": false}');

        $messageText = $this->getFaker()->text;
        $message = M::mock(NotificationMessageInterface::class);

        $message->shouldReceive('setUsers')
            ->with($users);

        $factory->shouldReceive('createEmailNotification')
            ->once()
            ->with($message)
            ->andReturn(new SwiftMailerNotification($messageText));

        $manager->shouldReceive('trigger')
            ->once()
            ->with(IsInstanceOf::anInstanceOf(ChainedNotification::class));

        $notificationManager = new NotificationManager($factory, $settings, $manager, $doctrine);
        $notificationManager->sendNotification('create_invoice', $message);
    }

    public function testSendSmsNotification()
    {
        $factory = M::mock(Factory::class);
        $settings = M::mock(SystemConfig::class);
        $manager = M::mock(Manager::class);
        $doctrine = M::mock(ManagerRegistry::class);
        $em = M::mock(ObjectManager::class);
        $doctrine->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $users = [];

        $phoneNumber1 = $this->getFaker()->phoneNumber;
        $phoneNumber2 = $this->getFaker()->phoneNumber;
        $user1 = new User();
        $user1->setMobile($phoneNumber1);
        $user2 = new User();
        $user2->setMobile($phoneNumber2);
        $users[] = $user1;
        $users[] = $user2;

        $repository = M::mock(ObjectRepository::class);

        $em->shouldReceive('getRepository')
            ->once()
            ->with(User::class)
            ->andReturn($repository);

        $repository->shouldReceive('findAll')
            ->once()
            ->andReturn($users);

        $settings->shouldReceive('get')
            ->once()
            ->with('notification/create_invoice')
            ->andReturn('{"email": false, "sms": true}');

        $messageText = $this->getFaker()->text;
        $message = M::mock(NotificationMessageInterface::class);

        $message->shouldReceive('setUsers')
            ->with($users);

        $message->shouldReceive('getUsers')
            ->andReturn($users);

        $factory->shouldReceive('createSmsNotification')
            ->once()
            ->with($phoneNumber1, $message)
            ->andReturn(new TwilioNotification($phoneNumber1, $messageText));

        $factory->shouldReceive('createSmsNotification')
            ->once()
            ->with($phoneNumber2, $message)
            ->andReturn(new TwilioNotification($phoneNumber2, $messageText));

        $manager->shouldReceive('trigger')
            ->once()
            ->with(IsInstanceOf::anInstanceOf(ChainedNotification::class));

        $notificationManager = new NotificationManager($factory, $settings, $manager, $doctrine);
        $notificationManager->sendNotification('create_invoice', $message);
    }

    public function testSendSmsNotificationToSpecificUsers()
    {
        $factory = M::mock(Factory::class);
        $settings = M::mock(SystemConfig::class);
        $manager = M::mock(Manager::class);
        $doctrine = M::mock(ManagerRegistry::class);
        $em = M::mock(ObjectManager::class);
        $doctrine->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $users = [];

        $phoneNumber = $this->getFaker()->phoneNumber;
        $user1 = new User();
        $user1->setMobile($phoneNumber);
        $user2 = new User();
        $users[] = $user1;
        $users[] = $user2;

        $repository = M::mock(ObjectRepository::class);

        $em->shouldReceive('getRepository')
            ->once()
            ->with(User::class)
            ->andReturn($repository);

        $repository->shouldReceive('findAll')
            ->once()
            ->andReturn($users);

        $settings->shouldReceive('get')
            ->once()
            ->with('notification/create_invoice')
            ->andReturn('{"email": false, "sms": true}');

        $messageText = $this->getFaker()->text;
        $message = M::mock(NotificationMessageInterface::class);

        $message->shouldReceive('setUsers')
            ->with($users);

        $message->shouldReceive('getUsers')
            ->andReturn($users);

        $factory->shouldReceive('createSmsNotification')
            ->once()
            ->with($phoneNumber, $message)
            ->andReturn(new TwilioNotification($phoneNumber, $messageText));

        $manager->shouldReceive('trigger')
            ->once()
            ->with(IsInstanceOf::anInstanceOf(ChainedNotification::class));

        $notificationManager = new NotificationManager($factory, $settings, $manager, $doctrine);
        $notificationManager->sendNotification('create_invoice', $message);
    }

    public function testSendNotification()
    {
        $factory = M::mock(Factory::class);
        $settings = M::mock(SystemConfig::class);
        $manager = M::mock(Manager::class);
        $doctrine = M::mock(ManagerRegistry::class);
        $em = M::mock(ObjectManager::class);
        $doctrine->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $users = [];

        $phoneNumber = $this->getFaker()->phoneNumber;
        $user1 = new User();
        $user1->setMobile($phoneNumber);
        $user2 = new User();
        $users[] = $user1;
        $users[] = $user2;

        $repository = M::mock(ObjectRepository::class);

        $em->shouldReceive('getRepository')
            ->once()
            ->with(User::class)
            ->andReturn($repository);

        $repository->shouldReceive('findAll')
            ->once()
            ->andReturn($users);

        $settings->shouldReceive('get')
            ->once()
            ->with('notification/create_invoice')
            ->andReturn('{"email": true, "sms": true}');

        $messageText = $this->getFaker()->text;
        $message = M::mock(NotificationMessageInterface::class);

        $message->shouldReceive('setUsers')
            ->with($users);

        $message->shouldReceive('getUsers')
            ->andReturn($users);

        $factory->shouldReceive('createEmailNotification')
            ->once()
            ->with($message)
            ->andReturn(new SwiftMailerNotification($messageText));

        $factory->shouldReceive('createSmsNotification')
            ->once()
            ->with($phoneNumber, $message)
            ->andReturn(new TwilioNotification($phoneNumber, $messageText));

        $manager->shouldReceive('trigger')
            ->once()
            ->with(IsInstanceOf::anInstanceOf(ChainedNotification::class));

        $notificationManager = new NotificationManager($factory, $settings, $manager, $doctrine);
        $notificationManager->sendNotification('create_invoice', $message);
    }
}
