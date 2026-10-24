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

namespace SolidInvoice\UserBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Mailer\Test\Bridge\Zenstruck\Browser\MailerComponent;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use Zenstruck\Mailer\Test\TestEmail;

#[Group('functional')]
final class ResetPasswordTest extends WebTestCase
{
    use HasBrowser;
    use DoctrineTestTrait;
    use InteractsWithMailer;

    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->userRepository = self::getContainer()->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();
    }

    public function testPasswordReset(): void
    {
        $user = new User()
            ->setEmail('me@example.com')
            ->setPassword('a-test-password-that-will-be-changed-later')
            ->setEnabled(true);
        $this->em->persist($user);
        $this->em->flush();

        $emailLink = '';

        $this->browser()
            ->visit('/login')
            ->withProfiling()
            ->click('Forgot password?')
            ->assertOn('/reset-password')
            ->assertSeeIn('title', 'Forgot your password?')
            ->use(static fn (MailerComponent $component) => $component->assertNoEmailSent())
            ->withProfiling()
            ->interceptRedirects()
            ->fillField('reset_password_request[email]', 'me@example.com')
            ->click('Reset Password')
            ->use(static function (MailerComponent $component) use (&$emailLink): void {
                $component
                    ->assertSentEmailCount(1)
                    ->assertEmailSentTo('me@example.com', static function (TestEmail $email) use (&$emailLink): void {
                        $email
                            ->assertSubject('Reset your password')
                            ->assertHtmlContains('A password reset was requested for user')
                            ->assertHtmlContains('/reset-password/reset/');

                        self::assertSame(1, preg_match('#(/reset-password/reset/[a-zA-Z0-9]+)#', (string) $email->getHtmlBody(), $resetLink));
                        $emailLink = $resetLink[0];
                    });
            })
            ->followRedirect()
            ->assertOn('/reset-password/check-email')
            ->assertSee('An email has been sent. It contains a link you must click to reset your password')
            ->visit($emailLink)
            ->assertRedirectedTo('/reset-password/reset')
            ->followRedirect()
            ->assertOn('/reset-password/reset')
            ->assertSeeIn('title', 'Reset Password')
            ->fillField('reset_password[newPassword][first]', 'C0rrect-Horse-Battery-Staple!')
            ->fillField('reset_password[newPassword][second]', 'C0rrect-Horse-Battery-Staple!')
            ->click('Reset Password')
            ->assertRedirectedTo('/login')
            ->followRedirect()
            ->assertOn('/login')
            ->assertSee('Your password has been reset.')
        ;

        $user = $this->userRepository->findOneBy(['email' => 'me@example.com']);

        self::assertInstanceOf(User::class, $user);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($passwordHasher->isPasswordValid($user, 'C0rrect-Horse-Battery-Staple!'));
    }
}
