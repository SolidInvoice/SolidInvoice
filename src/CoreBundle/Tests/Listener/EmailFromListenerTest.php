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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Listener\EmailFromListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EmailFromListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithFromAddressConfigured(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig->shouldReceive('get')
            ->with('email/from_address')
            ->andReturn('info@example.com');

        $systemConfig->shouldReceive('get')
            ->with('email/from_name')
            ->andReturn('SolidInvoice');

        $tokenStorage = M::mock(TokenStorageInterface::class);

        $tokenStorage->shouldNotReceive('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertEquals([new Address('info@example.com', 'SolidInvoice')], $message->getFrom());
    }

    public function testWithoutFromAddress(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig->shouldReceive('get')
            ->with('email/from_address')
            ->andReturn(null);

        $token = M::mock(TokenInterface::class);

        $user = new User();
        $user->setEmail('test@example.com');

        $token->shouldReceive('getUser')
            ->once()
            ->withNoArgs()
            ->andReturn($user);

        $tokenStorage = M::mock(TokenStorageInterface::class);

        $tokenStorage->shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn($token);

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertEquals([new Address('test@example.com')], $message->getFrom());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(EmailFromListener::getSubscribedEvents()));
    }
}
