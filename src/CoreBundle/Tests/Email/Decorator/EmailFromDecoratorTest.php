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

namespace SolidInvoice\CoreBundle\Tests\Email\Decorator;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Email\Decorator\EmailFromDecorator;
use SolidInvoice\MailerBundle\Context;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Swift_Message;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class EmailFromDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDecorateWithFromAddressConfigured()
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

        $decorator = new EmailFromDecorator($systemConfig, $tokenStorage);

        $message = new Swift_Message();
        $decorator->decorate(new MessageEvent($message, Context::create()));

        static::assertSame(['info@example.com' => 'SolidInvoice'], $message->getFrom());
    }

    public function testDecorateWithOutFromAddress()
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

        $decorator = new EmailFromDecorator($systemConfig, $tokenStorage);

        $message = new Swift_Message();
        $decorator->decorate(new MessageEvent($message, Context::create()));

        static::assertSame(['test@example.com' => null], $message->getFrom());
    }
}
