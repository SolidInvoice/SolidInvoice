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

namespace SolidInvoice\UserBundle\Tests\EventSubscriber;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\EventSubscriber\UserEntitySubscriber;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Test for UserEntitySubscriber.
 *
 * @covers \SolidInvoice\UserBundle\EventSubscriber\UserEntitySubscriber
 */
final class UserEntitySubscriberTest extends TestCase
{
    /**
     * Test that prePersist does not send an email for verified users.
     */
    public function testPrePersistWithVerifiedUser(): void
    {
        $verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $emailVerifier = new EmailVerifier(
            $verifyEmailHelper,
            $mailer,
            $this->createMock(UserRepository::class)
        );

        $logger = new BufferingLogger();

        $subscriber = new UserEntitySubscriber($emailVerifier, $logger);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setVerified(true);

        $verifyEmailHelper
            ->expects($this->never())
            ->method('generateSignature');

        $mailer
            ->expects($this->never())
            ->method('send');

        $subscriber->prePersist($user);
    }

    /**
     * Test that prePersist attempts to send an email for unverified users.
     */
    public function testPrePersistWithUnverifiedUser(): void
    {
        $verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $emailVerifier = new EmailVerifier(
            $verifyEmailHelper,
            $mailer,
            $this->createMock(UserRepository::class)
        );

        $logger = new BufferingLogger();

        $subscriber = new UserEntitySubscriber($emailVerifier, $logger);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setVerified(false);

        $verifyEmailHelper
            ->expects($this->once())
            ->method('generateSignature')
            ->with('_verify_email', '00000000000000000000000000', 'test@example.com', ['id' => '1111111111111111111111'])
            ->willReturn(new VerifyEmailSignatureComponents(new DateTime('NOW'), 'https://example.com', 0))
        ;

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user) {
                return $email->getTo()[0]->getAddress() === $user->getEmail()
                    && $email->getSubject() === 'Please Confirm your Email'
                    && $email->getHtmlTemplate() === '@SolidInvoiceUser/Email/confirm_email.html.twig';
            }));

        $subscriber->prePersist($user);
    }

    /**
     * Test that prePersist handles exceptions properly.
     */
    public function testPrePersistWithException(): void
    {
        $verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $mailer = $this->createMock(MailerInterface::class);

        $emailVerifier = new EmailVerifier(
            $verifyEmailHelper,
            $mailer,
            $this->createMock(UserRepository::class)
        );

        $logger = new BufferingLogger();

        $subscriber = new UserEntitySubscriber($emailVerifier, $logger);

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password');
        $user->setVerified(false);

        $exception = new class() extends Exception implements TransportExceptionInterface {
            public function __construct()
            {
                parent::__construct('Transport error');
            }

            public function getDebug(): string
            {
                return '';
            }

            public function appendDebug(string $debug): void
            {
            }
        };
        $verifyEmailHelper
            ->expects($this->once())
            ->method('generateSignature')
            ->willThrowException($exception);

        $subscriber->prePersist($user);

        $logs = $logger->cleanLogs();
        $this->assertCount(1, $logs);
        $this->assertSame('Failed to send email confirmation', $logs[0][1]);
        $this->assertSame(['exception' => $exception], $logs[0][2]);
    }
}
