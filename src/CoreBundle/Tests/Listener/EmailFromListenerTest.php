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
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Listener\EmailFromListener;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Ulid;

final class EmailFromListenerTest extends TestCase
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

        $listener = new EmailFromListener($systemConfig, $tokenStorage, $this->companySelector(new Ulid()), 'SolidInvoice <no-reply@example.com>');

        $message = new TemplatedEmail();
        $envelope = Envelope::create($message);
        $listener(new MessageEvent($message, $envelope, 'smtp'));

        self::assertEquals([new Address('info@example.com', 'SolidInvoice')], $message->getFrom());
        self::assertSame('info@example.com', $envelope->getSender()->getAddress());
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

        $listener = new EmailFromListener($systemConfig, $tokenStorage, $this->companySelector(new Ulid()), 'SolidInvoice <no-reply@example.com>');

        $message = new TemplatedEmail();
        $envelope = Envelope::create($message);
        $listener(new MessageEvent($message, $envelope, 'smtp'));

        self::assertEquals([new Address('test@example.com')], $message->getFrom());
        self::assertSame('test@example.com', $envelope->getSender()->getAddress());
    }

    public function testDoesNothingForNonEmailMessages(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $tokenStorage = M::mock(TokenStorageInterface::class);
        $tokenStorage->shouldNotReceive('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage, $this->companySelector(new Ulid()), 'SolidInvoice <no-reply@example.com>');

        $message = new RawMessage('raw content');
        $envelope = new Envelope(new Address('sender@example.com'), [new Address('recipient@example.com')]);
        $listener(new MessageEvent($message, $envelope, 'smtp'));
    }

    public function testUsesSystemSenderWithoutCompany(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $tokenStorage = M::mock(TokenStorageInterface::class);
        $tokenStorage->shouldNotReceive('getToken');

        $listener = new EmailFromListener($systemConfig, $tokenStorage, $this->companySelector(null), 'SolidInvoice <no-reply@example.com>');

        $message = new TemplatedEmail();
        $envelope = Envelope::create($message->from('user@example.com')->to('user@example.com'));
        $listener(new MessageEvent($message, $envelope, 'smtp'));

        self::assertEquals([new Address('no-reply@example.com', 'SolidInvoice')], $message->getFrom());
        self::assertSame('no-reply@example.com', $envelope->getSender()->getAddress());
    }

    public function testClientEmailRepliesToTheCompanyEmail(): void
    {
        $systemConfig = $this->createStub(SystemConfig::class);
        $systemConfig->method('get')->willReturnMap([
            ['email/from_address', null, 'no-reply@example.com'],
            ['email/from_name', null, 'SolidInvoice'],
            ['system/company/contact_details/email', null, 'billing@acme.test'],
        ]);

        $listener = new EmailFromListener($systemConfig, $this->createStub(TokenStorageInterface::class), $this->companySelector(new Ulid()), 'SolidInvoice <no-reply@example.com>');

        $message = new InvoiceEmail(new Invoice());
        $listener(new MessageEvent($message, Envelope::create($message->to('client@example.com')), 'smtp'));

        self::assertEquals([new Address('billing@acme.test')], $message->getReplyTo());
    }

    public function testClientEmailRepliesToTheUserWithoutACompanyEmail(): void
    {
        $systemConfig = $this->createStub(SystemConfig::class);
        $systemConfig->method('get')->willReturnMap([
            ['email/from_address', null, 'no-reply@example.com'],
            ['email/from_name', null, 'SolidInvoice'],
            ['system/company/contact_details/email', null, null],
        ]);

        $user = new User();
        $user->setEmail('owner@acme.test');

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $listener = new EmailFromListener($systemConfig, $tokenStorage, $this->companySelector(new Ulid()), 'SolidInvoice <no-reply@example.com>');

        $message = new InvoiceEmail(new Invoice());
        $listener(new MessageEvent($message, Envelope::create($message->to('client@example.com')), 'smtp'));

        self::assertEquals([new Address('owner@acme.test')], $message->getReplyTo());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(EmailFromListener::getSubscribedEvents()));
        self::assertSame(['__invoke', -256], EmailFromListener::getSubscribedEvents()[MessageEvent::class]);
    }

    private function companySelector(?Ulid $company): CompanySelectorInterface
    {
        $selector = M::mock(CompanySelectorInterface::class);
        $selector->shouldReceive('getCompany')->andReturn($company);

        return $selector;
    }
}
