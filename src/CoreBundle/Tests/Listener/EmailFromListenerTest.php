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

use PHPUnit\Framework\MockObject\MockObject;
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
    public function testWithFromAddressConfigured(): void
    {
        /** @var SystemConfig&MockObject $systemConfig */
        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig->method('get')
            ->willReturnCallback(static function (string $key): ?string {
                return match ($key) {
                    'email/from_address' => 'info@example.com',
                    'email/from_name' => 'SolidInvoice',
                    default => null,
                };
            });

        /** @var TokenStorageInterface&MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects($this->never())
            ->method('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('info@example.com', 'SolidInvoice')], $message->getFrom());
    }

    public function testWithoutFromAddress(): void
    {
        /** @var SystemConfig&MockObject $systemConfig */
        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig->method('get')
            ->with('email/from_address')
            ->willReturn(null);

        /** @var TokenInterface&MockObject $token */
        $token = $this->createMock(TokenInterface::class);

        $user = new User();
        $user->setEmail('test@example.com');

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        /** @var TokenStorageInterface&MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $listener = new EmailFromListener($systemConfig, $tokenStorage);

        $message = new TemplatedEmail();
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals([new Address('test@example.com')], $message->getFrom());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(EmailFromListener::getSubscribedEvents()));
    }
}
